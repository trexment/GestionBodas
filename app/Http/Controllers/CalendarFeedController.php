<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Response;

class CalendarFeedController extends Controller
{
    public function feed($token): Response
    {
        $user = User::where('calendar_token', $token)->first();

        if (!$user) {
            // Check if token matches admin master or generate 404
            abort(404, 'Token de calendario no válido.');
        }

        $companyName = Setting::get('company_name', 'Eventos Musicales');

        if ($user->role === 'admin') {
            $events = Event::with(['client', 'dj', 'assistant'])->orderBy('event_date', 'asc')->get();
            $calName = "{$companyName} - Agenda Global";
        } elseif ($user->role === 'dj') {
            $events = Event::with(['client', 'assistant'])->where('dj_id', $user->id)->orderBy('event_date', 'asc')->get();
            $calName = "{$companyName} - DJ {$user->name}";
        } else {
            $events = Event::with(['client', 'dj'])->where('assistant_id', $user->id)->orderBy('event_date', 'asc')->get();
            $calName = "{$companyName} - Asistente {$user->name}";
        }

        $output = "BEGIN:VCALENDAR\r\n";
        $output .= "VERSION:2.0\r\n";
        $output .= "PRODID:-//EventosMusicales//Agenda//ES\r\n";
        $output .= "CALSCALE:GREGORIAN\r\n";
        $output .= "METHOD:PUBLISH\r\n";
        $output .= "X-WR-CALNAME:" . $this->escapeIcal($calName) . "\r\n";
        $output .= "X-WR-TIMEZONE:Europe/Madrid\r\n";

        foreach ($events as $event) {
            $date = Carbon::parse($event->event_date);
            $startTime = $event->start_time ?: ($event->dance_start_time ?: '13:00');
            $endTime = $event->calculated_dance_end_time ?: ($event->end_time ?: '23:00');

            $startDt = Carbon::parse($date->format('Y-m-d') . ' ' . substr($startTime, 0, 5));
            $endDt = Carbon::parse($date->format('Y-m-d') . ' ' . substr($endTime, 0, 5));
            if ($endDt->lt($startDt)) {
                $endDt->addDay();
            }

            $summary = "{$event->name}" . ($event->event_type_label ? " ({$event->event_type_label})" : "");
            $location = $event->location ?: 'Por determinar';

            $clientName = $event->client ? $event->client->name : 'Sin cliente';
            $clientPhone = $event->client ? $event->client->phone : 'Sin teléfono';
            $djName = $event->dj ? $event->dj->name : 'Sin DJ';
            $assistantName = $event->assistant ? $event->assistant->name : 'Sin Asistente';

            $description = "EVENTO: {$event->name}\\n";
            $description .= "TIPO: {$event->event_type_label}\\n";
            if ($event->setup_date || $event->start_time) {
                $description .= "MONTAJE: {$event->setup_schedule_label}\\n";
            }
            if ($event->dance_start_time) {
                $description .= "HORARIO BAILE: {$event->dance_schedule_label}\\n";
            }
            $description .= "CLIENTE: {$clientName} (Tel: {$clientPhone})\\n";
            $description .= "DJ: {$djName}\\n";
            $description .= "ASISTENTE: {$assistantName}\\n";
            $description .= "LUGAR: {$location}\\n";
            $description .= "ENLACE: " . url('/admin/events/' . $event->id);

            $output .= "BEGIN:VEVENT\r\n";
            $output .= "UID:event-" . $event->id . "-" . $date->format('Ymd') . "@eventosmusicales\r\n";
            $output .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $output .= "DTSTART:" . $startDt->format('Ymd\THis') . "\r\n";
            $output .= "DTEND:" . $endDt->format('Ymd\THis') . "\r\n";
            $output .= "SUMMARY:" . $this->escapeIcal($summary) . "\r\n";
            $output .= "LOCATION:" . $this->escapeIcal($location) . "\r\n";
            $output .= "DESCRIPTION:" . $this->escapeIcal($description) . "\r\n";
            $output .= "STATUS:CONFIRMED\r\n";
            $output .= "END:VEVENT\r\n";
        }

        $output .= "END:VCALENDAR\r\n";

        return response($output, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="eventos_musicales_agenda.ics"',
        ]);
    }

    private function escapeIcal($str)
    {
        return preg_replace('/([\,;])/', '\\\$1', $str);
    }
}
