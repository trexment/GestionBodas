<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Invoice;
use Carbon\Carbon;
use ZipArchive;

/**
 * Servicio encargado de generar informes de facturación, métricas financieras y exportaciones en Excel/CSV.
 */
class BillingReportService
{
    /**
     * Construye los datos completos del panel de facturación e ingresos según los filtros aplicados.
     */
    public static function buildReportData(array $filters = []): array
    {
        $season = $filters['season'] ?? 'all'; // 'all' | '2026' | '2027' | '2028' (Temporada = año de event_date)
        $year = $filters['year'] ?? 'all';
        $dateBasis = $filters['date_basis'] ?? 'payment_date'; // 'payment_date' | 'event_date'
        $quarter = $filters['quarter'] ?? 'all'; // 'all' | '1' | '2' | '3' | '4'
        $dateFrom = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $dateTo = !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : null;
        $paymentStatus = $filters['payment_status'] ?? 'with_payments'; // 'all' | 'with_payments' | 'paid' | 'partial' | 'unpaid'
        $paymentMethod = $filters['payment_method'] ?? 'all'; // 'all' | 'bizum' | 'transfer' | 'cash' | 'card' | 'other'
        $docType = $filters['doc_type'] ?? 'all'; // 'all' | 'factura' | 'recibo'
        $brand = $filters['brand'] ?? 'all'; // 'all' | 'nunez_and_son' | 'javnx'
        $eventStatusFilter = $filters['event_status'] ?? 'active'; // 'active' | 'confirmed_completed' | 'all'
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));

        // Si se selecciona trimestre con un año concreto, calcular el rango del trimestre si no hay date_from/date_to manual
        if ($year !== 'all' && is_numeric($year) && in_array((string) $quarter, ['1', '2', '3', '4'], true) && !$dateFrom && !$dateTo) {
            $qInt = (int) $quarter;
            $startMonth = (($qInt - 1) * 3) + 1;
            $dateFrom = Carbon::create((int) $year, $startMonth, 1)->startOfDay();
            $dateTo = (clone $dateFrom)->addMonths(2)->endOfMonth()->endOfDay();
        }

        \App\Models\EventProviderBooking::ensureTableExists();
        \App\Models\MaterialExpense::ensureTableExists();

        $events = Event::with(['client', 'quotes.items', 'invoices', 'providerBookings'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        $filteredEvents = [];
        $ledgerRows = [];
        $providerRows = [];
        $collectedByPartner = [];

        // Acumuladores de KPIs
        $kpis = [
            'events_count' => 0,
            'events_with_payment_count' => 0,
            'events_fully_paid_count' => 0,
            'events_partial_count' => 0,
            'events_unpaid_count' => 0,
            'total_agreed' => 0.0,
            'total_collected' => 0.0,
            'total_pending' => 0.0,
            'total_deposits_collected' => 0.0,
            'total_finals_collected' => 0.0,
            'by_method' => [
                'bizum' => ['label' => 'Bizum', 'icon' => '📱', 'amount' => 0.0, 'count' => 0],
                'transfer' => ['label' => 'Transferencia Bancaria', 'icon' => '🏦', 'amount' => 0.0, 'count' => 0],
                'cash' => ['label' => 'Efectivo', 'icon' => '💵', 'amount' => 0.0, 'count' => 0],
                'card' => ['label' => 'Tarjeta / TPV', 'icon' => '💳', 'amount' => 0.0, 'count' => 0],
                'other' => ['label' => 'Otro / No espec.', 'icon' => '💶', 'amount' => 0.0, 'count' => 0],
            ],
            'tax_summary' => [
                'invoices_count' => 0,
                'invoices_base' => 0.0,
                'invoices_tax' => 0.0,
                'invoices_total' => 0.0,
                'receipts_count' => 0,
                'receipts_total' => 0.0,
            ],
            'provider_summary' => [
                'events_count' => 0,
                'total_client_price' => 0.0,
                'total_cost' => 0.0,
                'deposits_paid' => 0.0,
                'finals_paid' => 0.0,
                'total_paid' => 0.0,
                'total_pending' => 0.0,
                'total_refund' => 0.0,
                'total_net_cost' => 0.0,
                'events_deposit_paid_count' => 0,
                'events_fully_paid_count' => 0,
                'events_unpaid_count' => 0,
            ],
            'provider_events_in_table_count' => 0,
            'provider_client_price_in_events' => 0.0,
            'provider_cost_in_events' => 0.0,
            'provider_paid_in_events' => 0.0,
            'provider_pending_in_events' => 0.0,
            'provider_refund_in_events' => 0.0,
            'provider_net_cost_in_events' => 0.0,
            'net_agreed_after_providers' => 0.0,
            'net_collected_after_providers' => 0.0,
        ];

        foreach ($events as $event) {
            $fb = $event->financial_breakdown;
            $prov = $event->external_provider_summary;

            // 1. Filtro por Marca
            if ($brand !== 'all' && $event->brand_clean !== $brand) {
                continue;
            }

            // 1b. Filtro por Temporada (Año de celebración del evento)
            if ($season !== 'all' && is_numeric($season)) {
                $evYear = $event->event_date ? (int) Carbon::parse($event->event_date)->year : null;
                if ($evYear !== (int) $season) {
                    continue;
                }
            }

            // 2. Filtro por Estado del Evento
            if ($eventStatusFilter === 'confirmed_completed') {
                if (!in_array($event->status, ['confirmed', 'completed'], true) && $fb['total_paid'] <= 0) {
                    continue;
                }
            } elseif ($eventStatusFilter === 'active') {
                // Excluir cancelados y rechazados salvo que tengan pagos registrados
                if (in_array($event->status, ['cancelled', 'rejected'], true) && $fb['total_paid'] <= 0) {
                    continue;
                }
            }

            // 3. Filtro por Buscador
            if ($search !== '') {
                $clientName = mb_strtolower((string) ($event->client?->name ?? ''));
                $clientDni = mb_strtolower((string) ($event->client?->dni ?? ''));
                $partnerName = mb_strtolower((string) ($event->partner_name ?? ''));
                $partnerDni = mb_strtolower((string) ($event->partner_dni ?? ''));
                $eventName = mb_strtolower((string) ($event->name ?? ''));
                $location = mb_strtolower((string) ($event->location ?? ''));
                $invNumbers = mb_strtolower($event->invoices->pluck('invoice_number')->implode(' '));
                $invNotes = mb_strtolower($event->invoices->pluck('notes')->implode(' ') . ' ' . ($event->deposit_notes ?? ''));

                $haystack = "{$eventName} {$clientName} {$clientDni} {$partnerName} {$partnerDni} {$location} {$invNumbers} {$invNotes}";
                if (!str_contains($haystack, $search)) {
                    continue;
                }
            }

            // 4. Filtrar pagos individuales de este evento según año, trimestre, fechas, método y tipo de documento
            $matchingPayments = [];
            foreach ($fb['payments_list'] as $payment) {
                // Filtro de tipo de documento
                if ($docType === 'factura' && ($payment['document_type'] ?? '') !== 'factura') {
                    continue;
                }
                if ($docType === 'recibo' && !in_array($payment['document_type'] ?? '', ['recibo', 'senal'], true)) {
                    continue;
                }

                // Filtro de método de pago
                if ($paymentMethod !== 'all') {
                    $pm = $payment['method'] ?? 'other';
                    if ($pm !== $paymentMethod) {
                        continue;
                    }
                }

                // Filtro de Fecha (según base: fecha de pago o fecha del evento)
                $targetDate = ($dateBasis === 'event_date')
                    ? ($event->event_date ? Carbon::parse($event->event_date) : null)
                    : ($payment['date'] ? Carbon::parse($payment['date']) : ($event->event_date ? Carbon::parse($event->event_date) : null));

                if ($year !== 'all' && is_numeric($year)) {
                    if (!$targetDate || (int) $targetDate->year !== (int) $year) {
                        continue;
                    }
                }

                if ($dateFrom && (!$targetDate || $targetDate->lt($dateFrom))) {
                    continue;
                }
                if ($dateTo && (!$targetDate || $targetDate->gt($dateTo))) {
                    continue;
                }

                $matchingPayments[] = array_merge($payment, [
                    'event' => $event,
                    'event_name' => $event->name,
                    'event_date' => $event->event_date,
                    'event_type_label' => $event->event_type_label,
                    'event_type_icon' => $event->event_type_icon,
                    'brand_label' => $event->brand_label,
                    'brand_icon' => $event->brand_icon,
                    'client_name' => $event->client?->name ?? 'Sin cliente asignado',
                    'client_dni' => $event->client?->dni ?: ($event->partner_dni ?: ''),
                    'client_phone' => $event->client_contact_phone ?: '',
                ]);
            }

            // 5. Determinar si el evento en sí entra en la tabla de Control por Eventos
            $eventMatchesDate = true;
            if ($dateBasis === 'event_date') {
                $evDate = $event->event_date ? Carbon::parse($event->event_date) : null;
                if ($year !== 'all' && is_numeric($year)) {
                    if (!$evDate || (int) $evDate->year !== (int) $year) {
                        $eventMatchesDate = false;
                    }
                }
                if ($dateFrom && (!$evDate || $evDate->lt($dateFrom))) {
                    $eventMatchesDate = false;
                }
                if ($dateTo && (!$evDate || $evDate->gt($dateTo))) {
                    $eventMatchesDate = false;
                }
            } else {
                // Base = fecha de cobro
                if (!empty($fb['payments_list'])) {
                    $eventMatchesDate = count($matchingPayments) > 0;
                } else {
                    // Si el evento aún no tiene pagos y el filtro permite ver pendientes, mirar fecha del evento
                    $evDate = $event->event_date ? Carbon::parse($event->event_date) : $event->created_at;
                    if ($year !== 'all' && is_numeric($year)) {
                        if (!$evDate || (int) $evDate->year !== (int) $year) {
                            $eventMatchesDate = false;
                        }
                    }
                    if ($dateFrom && (!$evDate || $evDate->lt($dateFrom))) {
                        $eventMatchesDate = false;
                    }
                    if ($dateTo && (!$evDate || $evDate->gt($dateTo))) {
                        $eventMatchesDate = false;
                    }
                }
            }

            // Si hay filtro de método de pago o tipo de documento y el evento no tiene ningún pago que coincida, excluir
            if (($paymentMethod !== 'all' || $docType !== 'all') && empty($matchingPayments)) {
                $eventMatchesDate = false;
            }

            // Filtro por Estado de Pago del evento
            $eventMatchesPaymentStatus = match ($paymentStatus) {
                'with_payments' => $fb['total_paid'] > 0 || !empty($matchingPayments),
                'paid' => $fb['payment_status'] === 'paid',
                'partial' => $fb['payment_status'] === 'partial',
                'unpaid' => $fb['payment_status'] === 'unpaid',
                default => true, // 'all'
            };

            if ($eventMatchesDate && $eventMatchesPaymentStatus) {
                $filteredEvents[] = [
                    'event' => $event,
                    'fb' => $fb,
                    'prov' => $prov,
                    'matching_payments' => $matchingPayments,
                ];

                // Sumar a KPIs de eventos
                $kpis['events_count']++;
                if ($fb['total_paid'] > 0) {
                    $kpis['events_with_payment_count']++;
                }
                if ($fb['payment_status'] === 'paid') {
                    $kpis['events_fully_paid_count']++;
                } elseif ($fb['payment_status'] === 'partial') {
                    $kpis['events_partial_count']++;
                } elseif ($fb['payment_status'] === 'unpaid') {
                    $kpis['events_unpaid_count']++;
                }

                $kpis['total_agreed'] += (float) $fb['quote_total'];
                $kpis['total_pending'] += (float) $fb['pending_amount'];
                $kpis['total_deposits_collected'] += (float) $fb['deposit_amount'];
                $kpis['total_finals_collected'] += (float) $fb['final_paid_amount'];

                if (!empty($prov['has_external_service'])) {
                    $kpis['provider_events_in_table_count']++;
                    $kpis['provider_client_price_in_events'] += (float) ($prov['client_price'] ?? 0);
                    $kpis['provider_cost_in_events'] += (float) ($prov['provider_total_cost'] ?? 0);
                    $kpis['provider_paid_in_events'] += (float) ($prov['total_paid'] ?? 0);
                    $kpis['provider_pending_in_events'] += (float) ($prov['pending_amount'] ?? 0);
                    $kpis['provider_refund_in_events'] += (float) ($prov['refund_amount'] ?? 0);
                    $kpis['provider_net_cost_in_events'] += (float) ($prov['net_cost'] ?? $prov['provider_total_cost'] ?? 0);
                }

                // Agregar los pagos que coinciden al Libro de Cobros y a los KPIs de ingresos cobrados
                foreach ($matchingPayments as $mp) {
                    $ledgerRows[] = $mp;

                    if (($mp['status'] ?? '') === 'paid') {
                        $amt = (float) $mp['total'];
                        $kpis['total_collected'] += $amt;

                        $mKey = in_array($mp['method'], ['bizum', 'transfer', 'cash', 'card'], true) ? $mp['method'] : 'other';
                        $kpis['by_method'][$mKey]['amount'] += $amt;
                        $kpis['by_method'][$mKey]['count']++;

                        // Registrar quién cobró físicamente este importe (Fran, Miguel, Cuenta Conjunta...)
                        $cBy = trim((string)($mp['collected_by'] ?? ''));
                        if ($cBy === '') {
                            $cBy = 'Cuenta Conjunta / Fondo Común';
                        }
                        $collectedByPartner[$cBy] = ($collectedByPartner[$cBy] ?? 0.0) + $amt;

                        if (($mp['document_type'] ?? '') === 'factura') {
                            $kpis['tax_summary']['invoices_count']++;
                            $kpis['tax_summary']['invoices_base'] += (float) $mp['base_amount'];
                            $kpis['tax_summary']['invoices_tax'] += (float) $mp['tax_amount'];
                            $kpis['tax_summary']['invoices_total'] += $amt;
                        } else {
                            $kpis['tax_summary']['receipts_count']++;
                            $kpis['tax_summary']['receipts_total'] += $amt;
                        }
                    }
                }
            }

            // 6. Control de Reservas y Pagos a Proveedores Externos (Fotomatón / Plataforma 360º)
            if ($prov['has_external_service']) {
                $provDateMatches = true;
                $evDateForProv = $event->event_date ? Carbon::parse($event->event_date) : null;
                $payDateForProv = $prov['final_date']
                    ? Carbon::parse($prov['final_date'])
                    : ($prov['deposit_date'] ? Carbon::parse($prov['deposit_date']) : $evDateForProv);
                $refDate = ($dateBasis === 'event_date') ? $evDateForProv : ($payDateForProv ?: $evDateForProv);

                if ($year !== 'all' && is_numeric($year)) {
                    if (!$refDate || (int) $refDate->year !== (int) $year) {
                        $provDateMatches = false;
                    }
                }
                if ($dateFrom && (!$refDate || $refDate->lt($dateFrom))) {
                    $provDateMatches = false;
                }
                if ($dateTo && (!$refDate || $refDate->gt($dateTo))) {
                    $provDateMatches = false;
                }

                if ($paymentMethod !== 'all') {
                    $depM = $prov['deposit_method'] ?? '';
                    $finM = $prov['final_method'] ?? '';
                    if ($depM !== $paymentMethod && $finM !== $paymentMethod) {
                        $provDateMatches = false;
                    }
                }

                if ($provDateMatches) {
                    $providerRows[] = [
                        'event' => $event,
                        'fb' => $fb,
                        'prov' => $prov,
                    ];

                    $kpis['provider_summary']['events_count']++;
                    $kpis['provider_summary']['total_client_price'] += (float) ($prov['client_price'] ?? 0);
                    $kpis['provider_summary']['total_cost'] += (float) $prov['provider_total_cost'];
                    $kpis['provider_summary']['deposits_paid'] += (float) $prov['deposit_amount'];
                    $kpis['provider_summary']['finals_paid'] += (float) $prov['final_amount'];
                    $kpis['provider_summary']['total_paid'] += (float) $prov['total_paid'];
                    $kpis['provider_summary']['total_pending'] += (float) $prov['pending_amount'];
                    $kpis['provider_summary']['total_refund'] += (float) ($prov['refund_amount'] ?? 0);
                    $kpis['provider_summary']['total_net_cost'] += (float) ($prov['net_cost'] ?? $prov['provider_total_cost']);

                    if ($prov['status'] === 'paid') {
                        $kpis['provider_summary']['events_fully_paid_count']++;
                    } elseif ($prov['status'] === 'deposit_paid') {
                        $kpis['provider_summary']['events_deposit_paid_count']++;
                    } else {
                        $kpis['provider_summary']['events_unpaid_count']++;
                    }
                }
            }
        }

        $kpis['net_agreed_after_providers'] = round($kpis['total_agreed'] - $kpis['provider_cost_in_events'], 2);
        $kpis['net_collected_after_providers'] = round($kpis['total_collected'] - $kpis['provider_paid_in_events'], 2);

        // Ordenar providerRows por fecha de evento ascendente (próximos primero) o más recientes
        usort($providerRows, function ($a, $b) {
            $evA = $a['event']->event_date ? Carbon::parse($a['event']->event_date)->timestamp : 0;
            $evB = $b['event']->event_date ? Carbon::parse($b['event']->event_date)->timestamp : 0;
            return $evB <=> $evA;
        });

        // Ordenar el Libro de Cobros por fecha de pago descendente
        usort($ledgerRows, function ($a, $b) {
            $tsA = $a['date'] ? Carbon::parse($a['date'])->timestamp : 0;
            $tsB = $b['date'] ? Carbon::parse($b['date'])->timestamp : 0;
            if ($tsA === $tsB) {
                return ($b['event_id'] ?? 0) <=> ($a['event_id'] ?? 0);
            }
            return $tsB <=> $tsA;
        });

        // Ordenar eventos: primero los que tienen pagos más recientes o fecha de evento más próxima
        usort($filteredEvents, function ($a, $b) use ($dateBasis) {
            if ($dateBasis === 'payment_date') {
                $dA = $a['fb']['last_payment_date'] ? Carbon::parse($a['fb']['last_payment_date'])->timestamp : 0;
                $dB = $b['fb']['last_payment_date'] ? Carbon::parse($b['fb']['last_payment_date'])->timestamp : 0;
                if ($dA !== $dB) {
                    return $dB <=> $dA;
                }
            }
            $evA = $a['event']->event_date ? Carbon::parse($a['event']->event_date)->timestamp : 0;
            $evB = $b['event']->event_date ? Carbon::parse($b['event']->event_date)->timestamp : 0;
            return $evB <=> $evA;
        });

        // 7. Carga y cálculo de Gastos y Compras de Material
        $materialQuery = \App\Models\MaterialExpense::with('event')->orderByDesc('purchase_date')->orderByDesc('id');

        if ($season !== 'all' && is_numeric($season)) {
            $materialQuery->where(function($q) use ($season) {
                $q->where('season', $season)
                  ->orWhereYear('purchase_date', (int) $season);
            });
        } elseif ($year !== 'all' && is_numeric($year)) {
            $materialQuery->whereYear('purchase_date', (int) $year);
        }

        if ($dateFrom) {
            $materialQuery->where('purchase_date', '>=', $dateFrom->format('Y-m-d'));
        }
        if ($dateTo) {
            $materialQuery->where('purchase_date', '<=', $dateTo->format('Y-m-d'));
        }
        if ($paymentMethod !== 'all') {
            $materialQuery->where('payment_method', $paymentMethod);
        }

        $materialRows = $materialQuery->get();
        $totalMaterial = 0.0;
        $materialByPaidBy = [];
        $materialByCategory = [];

        foreach ($materialRows as $mat) {
            $amt = (float) $mat->amount;
            $totalMaterial += $amt;
            $pBy = trim((string)($mat->paid_by ?: 'Fran'));
            $materialByPaidBy[$pBy] = ($materialByPaidBy[$pBy] ?? 0.0) + $amt;
            $cat = $mat->category ?: 'general';
            $materialByCategory[$cat] = ($materialByCategory[$cat] ?? 0.0) + $amt;
        }

        $kpis['material_summary'] = [
            'total_count' => $materialRows->count(),
            'total_amount' => round($totalMaterial, 2),
            'by_paid_by' => $materialByPaidBy,
            'by_category' => $materialByCategory,
        ];

        // 8. Liquidación y Reparto entre Socios (con arqueo y ajuste de cuentas separadas)
        // El gasto neto de proveedores a descontar es: Coste Factura Proveedor - Devolución/Liquidación de Cooperativa (Artístamente)
        $grossProviderCost = (float) ($kpis['provider_cost_in_events'] > 0 ? $kpis['provider_cost_in_events'] : ($kpis['provider_summary']['total_cost'] ?? 0.0));
        $totalProviderRefunds = (float) ($kpis['provider_refund_in_events'] ?? 0.0);
        $providerExpensesDeducted = max(0.0, round($grossProviderCost - $totalProviderRefunds, 2));

        $totalCollected = (float) $kpis['total_collected'];
        $netProfit = max(0.0, round($totalCollected - $providerExpensesDeducted - $totalMaterial, 2));

        // Acumular los gastos de proveedores pagados del bolsillo de cada socio (ej: Artístamente, Fotomatón, etc.)
        // Y las devoluciones de cooperativa (Artístamente) ingresadas en cuenta bancaria de cada socio
        $providerPaidByPartner = [];
        $providerRefundReceivedByPartner = [];
        $jointRefundReceived = 0.0;

        foreach ($filteredEvents as $fEv) {
            $evProv = $fEv['prov'];
            if (!empty($evProv['bookings']) && $evProv['bookings']->count() > 0) {
                foreach ($evProv['bookings'] as $b) {
                    $pBy = trim((string)($b->paid_by ?: 'Fran'));
                    $dep = ($b->deposit_paid && (float)$b->deposit_amount > 0) ? (float)$b->deposit_amount : 0.0;
                    $fin = ($b->final_paid && (float)$b->final_amount > 0) ? (float)$b->final_amount : 0.0;
                    $paid = round($dep + $fin, 2);
                    if ($paid <= 0 && (float)$b->provider_total_cost > 0 && ($b->deposit_paid || $b->final_paid)) {
                        $paid = (float)$b->provider_total_cost;
                    }
                    if ($paid > 0) {
                        $providerPaidByPartner[$pBy] = ($providerPaidByPartner[$pBy] ?? 0.0) + $paid;
                    }

                    if ($b->has_refund && (float)$b->refund_amount > 0) {
                        $rFran = (float)$b->refund_fran_calculated;
                        $rMiguel = (float)$b->refund_miguel_calculated;
                        $rEzequiel = (float)($b->refund_ezequiel_calculated ?? 0.0);
                        $rJoint = (float)$b->refund_joint_calculated;
                        if ($rFran > 0) {
                            $providerRefundReceivedByPartner['Fran'] = ($providerRefundReceivedByPartner['Fran'] ?? 0.0) + $rFran;
                        }
                        if ($rMiguel > 0) {
                            $providerRefundReceivedByPartner['Miguel'] = ($providerRefundReceivedByPartner['Miguel'] ?? 0.0) + $rMiguel;
                        }
                        if ($rEzequiel > 0) {
                            $providerRefundReceivedByPartner['Ezequiel'] = ($providerRefundReceivedByPartner['Ezequiel'] ?? 0.0) + $rEzequiel;
                        }
                        if ($rJoint > 0) {
                            $jointRefundReceived += $rJoint;
                        }
                    }
                }
            } elseif (!empty($evProv['has_external_service'])) {
                if ((float)($evProv['total_paid'] ?? 0) > 0) {
                    $pBy = trim((string)($evProv['paid_by'] ?: 'Fran'));
                    $providerPaidByPartner[$pBy] = ($providerPaidByPartner[$pBy] ?? 0.0) + (float)$evProv['total_paid'];
                }
                if (!empty($evProv['has_refund']) && (float)($evProv['refund_amount'] ?? 0) > 0) {
                    $rFran = (float)($evProv['refund_fran_amount'] ?? 0);
                    $rMiguel = (float)($evProv['refund_miguel_amount'] ?? 0);
                    $rEzequiel = (float)($evProv['refund_ezequiel_amount'] ?? 0);
                    $rJoint = (float)($evProv['refund_joint_amount'] ?? 0);
                    if ($rFran > 0) {
                        $providerRefundReceivedByPartner['Fran'] = ($providerRefundReceivedByPartner['Fran'] ?? 0.0) + $rFran;
                    }
                    if ($rMiguel > 0) {
                        $providerRefundReceivedByPartner['Miguel'] = ($providerRefundReceivedByPartner['Miguel'] ?? 0.0) + $rMiguel;
                    }
                    if ($rEzequiel > 0) {
                        $providerRefundReceivedByPartner['Ezequiel'] = ($providerRefundReceivedByPartner['Ezequiel'] ?? 0.0) + $rEzequiel;
                    }
                    if ($rJoint > 0) {
                        $jointRefundReceived += $rJoint;
                    }
                }
            }
        }

        $partners = \App\Models\Setting::getSettlementPartners();
        $partnersSettlement = [];

        foreach ($partners as $partner) {
            $pName = trim((string)($partner['name'] ?? 'Socio'));
            $pPct = (float) ($partner['percentage'] ?? 50.0);
            $pShare = round($netProfit * ($pPct / 100), 2);
            $pMatReimb = round((float) ($materialByPaidBy[$pName] ?? 0.0), 2);
            $pProvReimb = round((float) ($providerPaidByPartner[$pName] ?? 0.0), 2);
            $pTotalReimb = round($pMatReimb + $pProvReimb, 2);
            $pTotalToReceive = round($pShare + $pTotalReimb, 2);

            // Dinero cobrado físicamente por este socio en su propia cuenta / Bizum de clientes
            $pClientCollected = round((float) ($collectedByPartner[$pName] ?? 0.0), 2);
            // Dinero devuelto por Artístamente / cooperativa que entró en la cuenta bancaria de este socio
            $pRefundReceived = round((float) ($providerRefundReceivedByPartner[$pName] ?? 0.0), 2);
            // Total dinero físico real en posesión del socio
            $pCollectedInHand = round($pClientCollected + $pRefundReceived, 2);

            // Arqueo: Lo que tiene en mano menos lo que le corresponde percibir
            // > 0 => Tiene exceso de dinero en su cuenta (debe transferir)
            // < 0 => Tiene defecto de dinero en su cuenta (debe recibir)
            $pBalanceDiff = round($pCollectedInHand - $pTotalToReceive, 2);

            $partnersSettlement[] = [
                'name' => $pName,
                'percentage' => $pPct,
                'profit_share' => $pShare,
                'material_reimbursement' => $pMatReimb,
                'provider_reimbursement' => $pProvReimb,
                'reimbursement' => $pTotalReimb,
                'total_to_receive' => $pTotalToReceive,
                'client_collected' => $pClientCollected,
                'refund_received' => $pRefundReceived,
                'collected_in_hand' => $pCollectedInHand,
                'balance_diff' => $pBalanceDiff,
            ];
        }

        // Dinero cobrado en Cuenta Conjunta / Empresa / Fondo Común (incluyendo devoluciones si fueron a la cuenta conjunta)
        $jointCollected = 0.0;
        foreach ($collectedByPartner as $cName => $cAmt) {
            $cNameLower = mb_strtolower((string)$cName);
            if (str_contains($cNameLower, 'conjunt') || str_contains($cNameLower, 'empresa') || str_contains($cNameLower, 'comun') || str_contains($cNameLower, 'común') || str_contains($cNameLower, 'fondo')) {
                $jointCollected += (float)$cAmt;
            }
        }
        $jointCollected = round($jointCollected + $jointRefundReceived, 2);

        // Instrucción de liquidación y transferencias directas entre socios (soporta 2, 3 o N socios)
        $transferInstruction = null;
        $instructionsList = [];
        $debtors = [];
        $creditors = [];

        foreach ($partnersSettlement as $p) {
            $diff = (float)$p['balance_diff'];
            if ($diff > 0.05) {
                $debtors[] = ['name' => $p['name'], 'amount' => $diff];
            } elseif ($diff < -0.05) {
                $creditors[] = ['name' => $p['name'], 'amount' => abs($diff)];
            }
        }

        $dIdx = 0;
        $cIdx = 0;
        while ($dIdx < count($debtors) && $cIdx < count($creditors)) {
            $payAmt = min($debtors[$dIdx]['amount'], $creditors[$cIdx]['amount']);
            if ($payAmt > 0.05) {
                $instructionsList[] = [
                    'from' => $debtors[$dIdx]['name'],
                    'to' => $creditors[$cIdx]['name'],
                    'amount' => round($payAmt, 2),
                    'text' => "{$debtors[$dIdx]['name']} debe transferir " . number_format($payAmt, 2, ',', '.') . " € a {$creditors[$cIdx]['name']}.",
                ];
            }
            $debtors[$dIdx]['amount'] -= $payAmt;
            $creditors[$cIdx]['amount'] -= $payAmt;
            if ($debtors[$dIdx]['amount'] <= 0.05) $dIdx++;
            if ($creditors[$cIdx]['amount'] <= 0.05) $cIdx++;
        }

        if (!empty($instructionsList)) {
            $totalTransfer = array_sum(array_column($instructionsList, 'amount'));
            $combinedText = implode(' • ', array_column($instructionsList, 'text'));
            $transferInstruction = [
                'has_transfer' => true,
                'amount' => round($totalTransfer, 2),
                'text' => $combinedText,
                'list' => $instructionsList,
            ];
        } else {
            $partnerNames = implode(', ', array_column($partnersSettlement, 'name'));
            $transferInstruction = [
                'has_transfer' => false,
                'amount' => 0.0,
                'text' => "Las cuentas entre {$partnerNames} están perfectamente cuadradas (0,00 € de diferencia).",
                'list' => [],
            ];
        }

        $kpis['settlement_summary'] = [
            'total_collected' => round($totalCollected, 2),
            'collected_by_partner' => $collectedByPartner,
            'joint_collected' => $jointCollected,
            'gross_provider_expenses' => round($grossProviderCost, 2),
            'provider_refunds' => round($totalProviderRefunds, 2),
            'provider_expenses' => round($providerExpensesDeducted, 2),
            'providers_by_paid_by' => $providerPaidByPartner,
            'provider_refunds_by_partner' => $providerRefundReceivedByPartner,
            'joint_refund' => round($jointRefundReceived, 2),
            'material_expenses' => round($totalMaterial, 2),
            'material_by_paid_by' => $materialByPaidBy,
            'net_profit' => $netProfit,
            'partners' => $partnersSettlement,
            'transfer_instruction' => $transferInstruction,
        ];

        return [
            'filters' => $filters,
            'events_rows' => $filteredEvents,
            'ledger_rows' => $ledgerRows,
            'provider_rows' => $providerRows,
            'material_rows' => $materialRows,
            'kpis' => $kpis,
        ];
    }

    /**
     * Genera un archivo Excel (.xlsx) nativo OpenXML con 3 pestañas (Control por Eventos, Libro de Cobros, Resumen).
     * Devuelve la ruta al archivo temporal listo para descargar.
     */
    public static function generateXlsx(array $reportData): string
    {
        $eventsRows = $reportData['events_rows'] ?? [];
        $ledgerRows = $reportData['ledger_rows'] ?? [];
        $kpis = $reportData['kpis'] ?? [];

        // Hoja 1: Control de Pagos por Evento
        $sheet1Rows = [];
        $sheet1Rows[] = [
            'ID Evento',
            'Evento',
            'Tipo Evento',
            'Marca',
            'Fecha Evento',
            'Estado Evento',
            'Cliente',
            'DNI / NIF',
            'Teléfono',
            'Total Acordado (€)',
            'Señal Pagada (€)',
            'Fecha Pago Señal',
            'Método Pago Señal',
            'Liquidación / Resto Cobrado (€)',
            'Fecha Cobro Final',
            'Método Cobro Final',
            'Total Cobrado (€)',
            'Pendiente de Cobro (€)',
            'PVP Fotomatón/360º en Presupuesto (€)',
            'Gasto Proveedor Fotomatón/360º (€)',
            'Pagado a Proveedor (€)',
            'Pendiente a Proveedor (€)',
            'Neto Evento (Descontando Proveedor) (€)',
            '% Cobrado',
            'Estado de Pago',
            'Documentos Emitidos',
        ];

        foreach ($eventsRows as $row) {
            /** @var \App\Models\Event $ev */
            $ev = $row['event'];
            $fb = $row['fb'];
            $prov = $row['prov'] ?? $ev->external_provider_summary;
            $hasProv = !empty($prov['has_external_service']);
            $provClientPrice = $hasProv ? round((float) ($prov['client_price'] ?? 0), 2) : 0.0;
            $provCost = $hasProv ? round((float) ($prov['provider_total_cost'] ?? 0), 2) : 0.0;
            $provPaid = $hasProv ? round((float) ($prov['total_paid'] ?? 0), 2) : 0.0;
            $provPending = $hasProv ? round((float) ($prov['pending_amount'] ?? 0), 2) : 0.0;
            $netEvent = round((float) $fb['quote_total'] - $provCost, 2);
            $docsList = $ev->invoices->where('status', '!=', 'cancelled')->pluck('invoice_number')->implode(', ');

            $sheet1Rows[] = [
                (int) $ev->id,
                (string) $ev->name,
                (string) $ev->event_type_label,
                (string) $ev->brand_label,
                $ev->event_date ? $ev->event_date->format('d/m/Y') : 'Sin fecha',
                (string) $ev->status_label,
                (string) ($ev->client?->name ?? 'Sin cliente'),
                (string) ($ev->client?->dni ?: ($ev->partner_dni ?: '')),
                (string) ($ev->client_contact_phone ?: ''),
                round((float) $fb['quote_total'], 2),
                round((float) $fb['deposit_amount'], 2),
                $fb['deposit_date'] ? Carbon::parse($fb['deposit_date'])->format('d/m/Y') : '-',
                $fb['deposit_paid'] ? (string) $fb['deposit_method_label'] : '-',
                round((float) $fb['final_paid_amount'], 2),
                $fb['final_paid_date'] ? Carbon::parse($fb['final_paid_date'])->format('d/m/Y') : '-',
                $fb['final_paid_amount'] > 0 ? (string) ($fb['final_method_label'] ?: 'No especificado') : '-',
                round((float) $fb['total_paid'], 2),
                round((float) $fb['pending_amount'], 2),
                $provClientPrice,
                $provCost,
                $provPaid,
                $provPending,
                $netEvent,
                $fb['paid_percentage'] . '%',
                (string) $fb['payment_status_label'],
                $docsList ?: 'Sin documento emitido',
            ];
        }

        // Fila de Totales en Hoja 1
        $sheet1Rows[] = [
            '',
            'TOTALES (' . count($eventsRows) . ' eventos)',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            round((float) ($kpis['total_agreed'] ?? 0), 2),
            round((float) ($kpis['total_deposits_collected'] ?? 0), 2),
            '',
            '',
            round((float) ($kpis['total_finals_collected'] ?? 0), 2),
            '',
            '',
            round((float) ($kpis['total_collected'] ?? 0), 2),
            round((float) ($kpis['total_pending'] ?? 0), 2),
            round((float) ($kpis['provider_client_price_in_events'] ?? 0), 2),
            round((float) ($kpis['provider_cost_in_events'] ?? 0), 2),
            round((float) ($kpis['provider_paid_in_events'] ?? 0), 2),
            round((float) ($kpis['provider_pending_in_events'] ?? 0), 2),
            round((float) ($kpis['net_agreed_after_providers'] ?? 0), 2),
            '',
            '',
            '',
        ];

        // Hoja 2: Libro Cronológico de Cobros, Recibos y Facturas
        $sheet2Rows = [];
        $sheet2Rows[] = [
            'Fecha de Cobro / Emisión',
            'Nº Documento',
            'Tipo',
            'Concepto',
            'Método de Pago',
            'Estado Cobro',
            'Evento',
            'Fecha Evento',
            'Marca',
            'Cliente',
            'DNI / NIF',
            'Teléfono',
            'Base Imponible (€)',
            'IVA (%)',
            'Importe IVA (€)',
            'Importe Total (€)',
            'Notas / Detalle',
        ];

        $sumBase = 0.0;
        $sumTax = 0.0;
        $sumTotal = 0.0;

        foreach ($ledgerRows as $item) {
            $docTypeLabel = match ($item['document_type'] ?? '') {
                'factura' => 'Factura Oficial',
                'recibo' => 'Recibo de Pago',
                default => 'Señal de Reserva',
            };
            $statusLabel = ($item['status'] ?? '') === 'paid' ? 'COBRADO' : 'PENDIENTE';

            $base = round((float) ($item['base_amount'] ?? 0), 2);
            $tax = round((float) ($item['tax_amount'] ?? 0), 2);
            $tot = round((float) ($item['total'] ?? 0), 2);

            if (($item['status'] ?? '') === 'paid') {
                $sumBase += $base;
                $sumTax += $tax;
                $sumTotal += $tot;
            }

            $sheet2Rows[] = [
                $item['date'] ? Carbon::parse($item['date'])->format('d/m/Y') : '-',
                (string) ($item['document_number'] ?? '-'),
                $docTypeLabel,
                (string) ($item['concept'] ?? ''),
                (string) ($item['method_label'] ?? 'No especificado'),
                $statusLabel,
                (string) ($item['event_name'] ?? ''),
                $item['event_date'] ? Carbon::parse($item['event_date'])->format('d/m/Y') : '-',
                (string) ($item['brand_label'] ?? ''),
                (string) ($item['client_name'] ?? ''),
                (string) ($item['client_dni'] ?? ''),
                (string) ($item['client_phone'] ?? ''),
                $base,
                round((float) ($item['tax_rate'] ?? 0), 2),
                $tax,
                $tot,
                (string) ($item['notes'] ?? ''),
            ];
        }

        $sheet2Rows[] = [
            '',
            'TOTAL COBRADO (' . count($ledgerRows) . ' reg.)',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            round($sumBase, 2),
            '',
            round($sumTax, 2),
            round($sumTotal, 2),
            '',
        ];

        // Hoja 3: Reservas y Pagos a Empresa Fotomatón / Plataforma 360º
        $providerRows = $reportData['provider_rows'] ?? [];
        $provKpis = $kpis['provider_summary'] ?? [];

        $sheetProvRows = [];
        $sheetProvRows[] = [
            'ID Evento',
            'Evento',
            'Fecha Evento',
            'Cliente',
            'Empresa / Proveedor',
            'Servicio (Fotomatón / 360º / Artístamente)',
            'PVP en Presupuesto Cliente (€)',
            'Total Factura Proveedor (€)',
            'Devolución Artístamente / Cooperativa (€)',
            'Coste Neto Real (€)',
            '1º Pago: Señal Pagada (€)',
            'Fecha Pago Señal',
            'Método Pago Señal',
            '2º Pago: Resto Pagado (€)',
            'Fecha Pago Resto',
            'Método Pago Resto',
            'Total Pagado a Proveedor (€)',
            'Pendiente de Pagar a Proveedor (€)',
            'Pagado Por',
            'Estado Reserva Proveedor',
            'Observaciones',
        ];

        foreach ($providerRows as $pRow) {
            /** @var \App\Models\Event $ev */
            $ev = $pRow['event'];
            $prov = $pRow['prov'];

            $sheetProvRows[] = [
                (int) $ev->id,
                (string) $ev->name,
                $ev->event_date ? $ev->event_date->format('d/m/Y') : 'Sin fecha',
                (string) ($ev->client?->name ?? 'Sin cliente'),
                (string) ($prov['provider_name'] ?? ''),
                (string) ($prov['service_description'] ?? ''),
                round((float) ($prov['client_price'] ?? 0), 2),
                round((float) ($prov['provider_total_cost'] ?? 0), 2),
                round((float) ($prov['refund_amount'] ?? 0), 2),
                round((float) ($prov['net_cost'] ?? $prov['provider_total_cost'] ?? 0), 2),
                round((float) ($prov['deposit_amount'] ?? 0), 2),
                $prov['deposit_date'] ? Carbon::parse($prov['deposit_date'])->format('d/m/Y') : '-',
                $prov['deposit_paid'] ? (string) ($prov['deposit_method_label'] ?? '-') : '-',
                round((float) ($prov['final_amount'] ?? 0), 2),
                $prov['final_date'] ? Carbon::parse($prov['final_date'])->format('d/m/Y') : '-',
                $prov['final_paid'] ? (string) ($prov['final_method_label'] ?? '-') : '-',
                round((float) ($prov['total_paid'] ?? 0), 2),
                round((float) ($prov['pending_amount'] ?? 0), 2),
                (string) ($prov['paid_by'] ?? 'Fran'),
                (string) ($prov['status_label'] ?? ''),
                (string) ($prov['notes'] ?? ''),
            ];
        }

        $sheetProvRows[] = [
            '',
            'TOTALES PROVEEDOR (' . count($providerRows) . ' eventos)',
            '',
            '',
            '',
            '',
            round((float) ($provKpis['total_client_price'] ?? 0), 2),
            round((float) ($provKpis['total_cost'] ?? 0), 2),
            round((float) ($provKpis['total_refund'] ?? 0), 2),
            round((float) ($provKpis['total_net_cost'] ?? $provKpis['total_cost'] ?? 0), 2),
            round((float) ($provKpis['deposits_paid'] ?? 0), 2),
            '',
            '',
            round((float) ($provKpis['finals_paid'] ?? 0), 2),
            '',
            '',
            round((float) ($provKpis['total_paid'] ?? 0), 2),
            round((float) ($provKpis['total_pending'] ?? 0), 2),
            '',
            '',
            '',
        ];

        // Hoja 4: Compras y Gastos de Material Técnico y Fungible
        $materialRows = $reportData['material_rows'] ?? [];
        $matSummary = $kpis['material_summary'] ?? [];

        $sheetMaterialRows = [];
        $sheetMaterialRows[] = [
            'ID Gasto',
            'Fecha Compra',
            'Concepto / Descripción',
            'Categoría',
            'Proveedor / Comercio',
            'Temporada',
            'Evento Asociado',
            'Pagado Por (Socio/Caja)',
            'Método de Pago',
            'Nº Factura / Ticket',
            'Importe (€)',
            'Notas / Observaciones',
        ];

        foreach ($materialRows as $mRow) {
            $catInfo = $mRow->category_info;
            $evName = $mRow->event ? $mRow->event->name : 'Gasto General';
            $sheetMaterialRows[] = [
                (int) $mRow->id,
                $mRow->purchase_date ? $mRow->purchase_date->format('d/m/Y') : '-',
                (string) $mRow->concept,
                (string) ($catInfo['label'] ?? ucfirst($mRow->category)),
                (string) ($mRow->supplier ?? '-'),
                (string) ($mRow->season ?? '-'),
                $evName,
                (string) ($mRow->paid_by ?? 'Fran'),
                (string) $mRow->payment_method_label,
                (string) ($mRow->invoice_number ?? '-'),
                round((float) $mRow->amount, 2),
                (string) ($mRow->notes ?? ''),
            ];
        }

        $sheetMaterialRows[] = [
            '',
            'TOTAL COMPRAS DE MATERIAL (' . count($materialRows) . ' compras)',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            round((float) ($matSummary['total_amount'] ?? 0), 2),
            '',
        ];

        // Hoja 5: Liquidación y Reparto entre Socios
        $settlement = $kpis['settlement_summary'] ?? [];
        $partners = $settlement['partners'] ?? [];

        $sheetSettlementRows = [
            ['CUENTA DE RESULTADOS Y BENEFICIO NETO DISPONIBLE', '', '', '', '', '', '', '', '', 'Importe (€)'],
            ['(+) Total Ingresos Cobrados de Clientes', '', '', '', '', '', '', '', '', round((float) ($settlement['total_collected'] ?? 0), 2)],
            ['(-) Gasto Bruto Facturas Proveedores (Artístamente / Fotomatón / 360)', '', '', '', '', '', '', '', '', round((float) ($settlement['gross_provider_expenses'] ?? $settlement['provider_expenses'] ?? 0), 2)],
            ['(+) Devoluciones Cobradas de Artístamente / Cooperativas', '', '', '', '', '', '', '', '', round((float) ($settlement['provider_refunds'] ?? 0), 2)],
            ['(=) Gasto Neto Real Proveedores Asumido', '', '', '', '', '', '', '', '', round((float) ($settlement['provider_expenses'] ?? 0), 2)],
            ['(-) Compras y Gastos de Material Deducibles', '', '', '', '', '', '', '', '', round((float) ($settlement['material_expenses'] ?? 0), 2)],
            ['(=) BENEFICIO NETO DISPONIBLE A REPARTIR', '', '', '', '', '', '', '', '', round((float) ($settlement['net_profit'] ?? 0), 2)],
            ['---', '', '', '', '', '', '', '', '', ''],
            ['CUADRO DE LIQUIDACIÓN Y ARQUEO ENTRE SOCIOS', '', '', '', '', '', '', '', '', ''],
            ['Socio / Destinatario', '% Participación', 'Cuota Beneficio (€)', 'Reembolso Material (€)', 'Reembolso Prov. Adelantado (€)', 'TOTAL A PERCIBIR (€)', 'Cobros Clientes en Cuenta (€)', 'Devolución Artístamente (€)', 'Total Dinero en Mano (€)', 'Ajuste / Saldo (€)'],
        ];

        $totBenefit = 0.0;
        $totMatReimb = 0.0;
        $totProvReimb = 0.0;
        $totFinal = 0.0;
        $totClientCol = 0.0;
        $totRefundRec = 0.0;
        $totInHand = 0.0;

        foreach ($partners as $p) {
            $share = round((float) ($p['profit_share'] ?? 0), 2);
            $mReimb = round((float) ($p['material_reimbursement'] ?? 0), 2);
            $prReimb = round((float) ($p['provider_reimbursement'] ?? 0), 2);
            $final = round((float) ($p['total_to_receive'] ?? 0), 2);
            $clCol = round((float) ($p['client_collected'] ?? 0), 2);
            $rfRec = round((float) ($p['refund_received'] ?? 0), 2);
            $inHnd = round((float) ($p['collected_in_hand'] ?? 0), 2);
            $balDiff = round((float) ($p['balance_diff'] ?? 0), 2);

            $totBenefit += $share;
            $totMatReimb += $mReimb;
            $totProvReimb += $prReimb;
            $totFinal += $final;
            $totClientCol += $clCol;
            $totRefundRec += $rfRec;
            $totInHand += $inHnd;

            $sheetSettlementRows[] = [
                (string) ($p['name'] ?? 'Socio'),
                round((float) ($p['percentage'] ?? 0), 2) . '%',
                $share,
                $mReimb,
                $prReimb,
                $final,
                $clCol,
                $rfRec,
                $inHnd,
                $balDiff,
            ];
        }

        $sheetSettlementRows[] = [
            'TOTALES LIQUIDACIÓN',
            '100.00%',
            round($totBenefit, 2),
            round($totMatReimb, 2),
            round($totProvReimb, 2),
            round($totFinal, 2),
            round($totClientCol, 2),
            round($totRefundRec, 2),
            round($totInHand, 2),
            '',
        ];

        if (!empty($settlement['transfer_instruction']['text'])) {
            $sheetSettlementRows[] = ['---', '', '', '', '', '', '', '', '', ''];
            $sheetSettlementRows[] = ['INSTRUCCIÓN DE AJUSTE BANCARIO:', $settlement['transfer_instruction']['text'], '', '', '', '', '', '', '', ''];
        }

        // Hoja 6: Resumen Ejecutivo e Ingresos por Método de Pago
        $sheet3Rows = [
            ['Indicador / Concepto', 'Nº Registros', 'Importe (€)'],
            ['Total Contratado / Presupuestado', (int) ($kpis['events_count'] ?? 0), round((float) ($kpis['total_agreed'] ?? 0), 2)],
            ['Total Ingresado / Cobrado de Clientes', (int) ($kpis['events_with_payment_count'] ?? 0), round((float) ($kpis['total_collected'] ?? 0), 2)],
            ['Total Pendiente de Cobro de Clientes', (int) ($kpis['events_partial_count'] ?? 0) + (int) ($kpis['events_unpaid_count'] ?? 0), round((float) ($kpis['total_pending'] ?? 0), 2)],
            ['Gasto Total Proveedores (Fotomatón / 360º)', (int) ($kpis['provider_events_in_table_count'] ?? 0), round((float) ($kpis['provider_cost_in_events'] ?? 0), 2)],
            ['Gasto Total Compras de Material y Equipos', (int) ($matSummary['total_count'] ?? 0), round((float) ($matSummary['total_amount'] ?? 0), 2)],
            ['Beneficio Neto Disponible a Repartir', (int) count($partners), round((float) ($settlement['net_profit'] ?? 0), 2)],
            ['---', '', ''],
            ['DESGLOSE POR MÉTODO DE PAGO', 'Nº Cobros', 'Total Cobrado (€)'],
            ['Bizum', (int) ($kpis['by_method']['bizum']['count'] ?? 0), round((float) ($kpis['by_method']['bizum']['amount'] ?? 0), 2)],
            ['Transferencia Bancaria', (int) ($kpis['by_method']['transfer']['count'] ?? 0), round((float) ($kpis['by_method']['transfer']['amount'] ?? 0), 2)],
            ['Efectivo', (int) ($kpis['by_method']['cash']['count'] ?? 0), round((float) ($kpis['by_method']['cash']['amount'] ?? 0), 2)],
            ['Tarjeta / TPV', (int) ($kpis['by_method']['card']['count'] ?? 0), round((float) ($kpis['by_method']['card']['amount'] ?? 0), 2)],
            ['Otros / No especificado', (int) ($kpis['by_method']['other']['count'] ?? 0), round((float) ($kpis['by_method']['other']['amount'] ?? 0), 2)],
            ['---', '', ''],
            ['LIQUIDACIÓN POR SOCIO (BENEFICIO + REEMBOLSO MATERIAL)', 'Cuota %', 'Total a Percibir (€)'],
        ];

        foreach ($partners as $p) {
            $sheet3Rows[] = [
                (string) ($p['name'] ?? 'Socio'),
                round((float) ($p['percentage'] ?? 0), 2) . '%',
                round((float) ($p['total_to_receive'] ?? 0), 2),
            ];
        }

        $sheet3Rows[] = ['---', '', ''];
        $sheet3Rows[] = ['DESGLOSE FISCAL (FACTURAS CON IVA vs RECIBOS)', 'Documentos', 'Importe (€)'];
        $sheet3Rows[] = ['Base Imponible en Facturas', (int) ($kpis['tax_summary']['invoices_count'] ?? 0), round((float) ($kpis['tax_summary']['invoices_base'] ?? 0), 2)];
        $sheet3Rows[] = ['IVA Repercutido en Facturas', (int) ($kpis['tax_summary']['invoices_count'] ?? 0), round((float) ($kpis['tax_summary']['invoices_tax'] ?? 0), 2)];
        $sheet3Rows[] = ['Total Facturas Oficiales (Con IVA)', (int) ($kpis['tax_summary']['invoices_count'] ?? 0), round((float) ($kpis['tax_summary']['invoices_total'] ?? 0), 2)];
        $sheet3Rows[] = ['Total Recibos y Señales (Sin IVA)', (int) ($kpis['tax_summary']['receipts_count'] ?? 0), round((float) ($kpis['tax_summary']['receipts_total'] ?? 0), 2)];

        return self::buildMultiSheetXlsxFile([
            'Control por Eventos' => $sheet1Rows,
            'Libro de Cobros y Facturas' => $sheet2Rows,
            'Reservas Fotomaton y 360' => $sheetProvRows,
            'Compras y Gastos Material' => $sheetMaterialRows,
            'Reparto y Liquidacion' => $sheetSettlementRows,
            'Resumen de Ingresos' => $sheet3Rows,
        ]);
    }

    /**
     * Empaqueta múltiples hojas en un archivo OpenXML (.xlsx) válido con cabeceras estilizadas.
     */
    protected static function buildMultiSheetXlsxFile(array $sheets): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'facturacion_') . '.xlsx';
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo temporal Excel (.xlsx).');
        }

        // [Content_Types].xml
        $sheetOverrides = '';
        $idx = 1;
        foreach ($sheets as $name => $_) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet' . $idx . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $idx++;
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $sheetOverrides
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/workbook.xml & xl/_rels/workbook.xml.rels
        $workbookSheets = '';
        $workbookRels = '';
        $idx = 1;
        foreach ($sheets as $sheetTitle => $_) {
            $safeTitle = htmlspecialchars(mb_substr($sheetTitle, 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $workbookSheets .= '<sheet name="' . $safeTitle . '" sheetId="' . $idx . '" r:id="rId' . $idx . '"/>';
            $workbookRels .= '<Relationship Id="rId' . $idx . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $idx . '.xml"/>';
            $idx++;
        }
        $stylesRelId = 'rId' . $idx;
        $workbookRels .= '<Relationship Id="' . $stylesRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $workbookSheets . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $workbookRels
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);

        // xl/styles.xml (Estilos: 0 = Normal, 1 = Header Indigo, 2 = Moneda, 3 = Fila Totales Negrita, 4 = Totales Moneda)
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1">'
            . '<numFmt numFmtId="164" formatCode="#,##0.00\ &quot;€&quot;"/>'
            . '</numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E1B4B"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFE2E8F0"/></left>'
            . '<right style="thin"><color rgb="FFE2E8F0"/></right>'
            . '<top style="thin"><color rgb="FFE2E8F0"/></top>'
            . '<bottom style="thin"><color rgb="FFE2E8F0"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="164" fontId="2" fillId="3" borderId="1" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // Hojas
        $idx = 1;
        foreach ($sheets as $_ => $rows) {
            $zip->addFromString('xl/worksheets/sheet' . $idx . '.xml', self::buildWorksheetXml($rows));
            $idx++;
        }

        $zip->close();
        return $tempFile;
    }

    /**
     * Genera el XML de una hoja individual de Excel con anchos de columna automáticos.
     */
    protected static function buildWorksheetXml(array $rows): string
    {
        $colMaxLengths = [];
        foreach ($rows as $row) {
            foreach (array_values($row) as $cIdx => $val) {
                $len = mb_strlen((string) $val);
                if (!isset($colMaxLengths[$cIdx]) || $len > $colMaxLengths[$cIdx]) {
                    $colMaxLengths[$cIdx] = $len;
                }
            }
        }

        $colsXml = '<cols>';
        foreach ($colMaxLengths as $cIdx => $maxLen) {
            $colNum = $cIdx + 1;
            $width = max(11, min(46, $maxLen + 4));
            $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $totalRows = count($rows);
        $sheetData = '<sheetData>';

        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $isHeader = ($rIdx === 0);
            $isTotalRow = ($rIdx === $totalRows - 1 && $totalRows > 2 && str_starts_with((string) ($row[1] ?? ''), 'TOTAL'));

            $rowHeightAttr = $isHeader ? ' ht="24" customHeight="1"' : ($isTotalRow ? ' ht="20" customHeight="1"' : '');
            $sheetData .= '<row r="' . $rowNum . '"' . $rowHeightAttr . '>';

            foreach (array_values($row) as $cIdx => $val) {
                $cellRef = self::columnLetter($cIdx) . $rowNum;

                if ($isHeader) {
                    $safeVal = htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $sheetData .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $safeVal . '</t></is></c>';
                } elseif (is_float($val)) {
                    $styleIdx = $isTotalRow ? 4 : 2;
                    $sheetData .= '<c r="' . $cellRef . '" s="' . $styleIdx . '"><v>' . number_format($val, 2, '.', '') . '</v></c>';
                } elseif (is_int($val)) {
                    $styleIdx = $isTotalRow ? 3 : 0;
                    $sheetData .= '<c r="' . $cellRef . '" s="' . $styleIdx . '"><v>' . $val . '</v></c>';
                } else {
                    $styleIdx = $isTotalRow ? 3 : 0;
                    $safeVal = htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $sheetData .= '<c r="' . $cellRef . '" s="' . $styleIdx . '" t="inlineStr"><is><t>' . $safeVal . '</t></is></c>';
                }
            }

            $sheetData .= '</row>';
        }

        $sheetData .= '</sheetData>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . $colsXml
            . $sheetData
            . '</worksheet>';
    }

    /**
     * Convierte un índice numérico de columna (base 0) en su letra equivalente de hoja de cálculo (A, B, ..., AA).
     */
    protected static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }
}
