/**
 * Offline Audio Cache Engine para Eventos Musicales
 * Permite almacenar canciones completas (MP3/M4A/WAV) en el almacenamiento local del dispositivo (IndexedDB)
 * para reproducirlas en fincas, bodegas o salones sin cobertura / conexión a internet.
 */

class OfflineAudioCacheEngine {
    constructor() {
        this.dbName = 'EventosMusicales_AudioCache_v1';
        this.storeName = 'cached_songs';
        this.db = null;
        this.activeBlobs = new Map();
        this._initPromise = this.init();
    }

    async init() {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                console.warn('IndexedDB no está disponible en este navegador.');
                return resolve(null);
            }

            const request = indexedDB.open(this.dbName, 1);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    const store = db.createObjectStore(this.storeName, { keyPath: 'id' });
                    store.createIndex('eventId', 'eventId', { unique: false });
                    store.createIndex('cachedAt', 'cachedAt', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                // Solicitar persistencia de almacenamiento para evitar que el navegador limpie la caché
                if (navigator.storage && navigator.storage.persist) {
                    navigator.storage.persist().catch(() => {});
                }
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error('Error al abrir IndexedDB:', event.target.error);
                resolve(null);
            };
        });
    }

    /**
     * Resuelve la URL real de descarga de un archivo de audio (Drive, local, streaming)
     */
    resolveAudioDownloadUrl(rawUrl) {
        if (!rawUrl || typeof rawUrl !== 'string') return null;
        let url = rawUrl.trim();

        if (url.includes('drive.google.com') || url.includes('/api/drive-stream/')) {
            const driveMatch = url.match(/\/d\/([a-zA-Z0-9_-]+)/) || url.match(/[?&]id=([a-zA-Z0-9_-]+)/) || url.match(/\/api\/drive-stream\/([a-zA-Z0-9_-]+)/);
            if (driveMatch && driveMatch[1]) {
                return `/api/drive-stream/${driveMatch[1]}`;
            }
        } else if (url.includes('dropbox.com')) {
            return url.replace('dl=0', 'raw=1');
        } else if (!url.startsWith('http') && !url.startsWith('/')) {
            return '/storage/' + url;
        }

        return url;
    }

    /**
     * Descarga y guarda una canción individual en IndexedDB
     */
    async cacheSong(songData, onProgress = null) {
        await this._initPromise;
        if (!this.db) throw new Error('IndexedDB no disponible');

        const songId = String(songData.id);
        const eventId = String(songData.event_id || songData.eventId || '');
        const targetUrl = this.resolveAudioDownloadUrl(songData.audio_file || songData.audioFile || songData.url);

        if (!targetUrl) {
            throw new Error('No hay archivo o URL de audio válida para descargar.');
        }

        if (onProgress) onProgress({ status: 'fetching', percent: 10, songId });

        const response = await fetch(targetUrl);
        if (!response.ok) {
            throw new Error(`Error al descargar audio (${response.status} ${response.statusText})`);
        }

        const contentLength = response.headers.get('content-length');
        let blob;

        if (contentLength && response.body && window.ReadableStream) {
            const total = parseInt(contentLength, 10);
            let loaded = 0;
            const reader = response.body.getReader();
            const chunks = [];

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
                loaded += value.length;
                if (onProgress && total > 0) {
                    const pct = Math.min(95, Math.round((loaded / total) * 100));
                    onProgress({ status: 'downloading', percent: pct, songId, loaded, total });
                }
            }

            blob = new Blob(chunks, { type: response.headers.get('content-type') || 'audio/mpeg' });
        } else {
            blob = await response.blob();
        }

        const record = {
            id: songId,
            eventId: eventId,
            title: songData.title || 'Canción',
            artist: songData.artist || '',
            moment: songData.moment || '',
            category: songData.category || '',
            blob: blob,
            mimeType: blob.type || 'audio/mpeg',
            size: blob.size,
            cachedAt: Date.now(),
            originalUrl: targetUrl
        };

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const req = store.put(record);

            req.onsuccess = () => {
                if (onProgress) onProgress({ status: 'completed', percent: 100, songId, size: blob.size });
                resolve(record);
            };

            req.onerror = (e) => {
                reject(e.target.error);
            };
        });
    }

    /**
     * Comprueba si una canción está guardada en la caché local
     */
    async isSongCached(songId) {
        await this._initPromise;
        if (!this.db) return false;

        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const req = store.get(String(songId));

            req.onsuccess = (e) => {
                resolve(!!e.target.result);
            };

            req.onerror = () => {
                resolve(false);
            };
        });
    }

    /**
     * Obtiene la URL Blob local lista para reproducir sin conexión
     */
    async getCachedAudioUrl(songId) {
        await this._initPromise;
        if (!this.db) return null;

        const sId = String(songId);
        if (this.activeBlobs.has(sId)) {
            return this.activeBlobs.get(sId);
        }

        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const req = store.get(sId);

            req.onsuccess = (e) => {
                const record = e.target.result;
                if (record && record.blob) {
                    const blobUrl = URL.createObjectURL(record.blob);
                    this.activeBlobs.set(sId, blobUrl);
                    resolve(blobUrl);
                } else {
                    resolve(null);
                }
            };

            req.onerror = () => {
                resolve(null);
            };
        });
    }

    /**
     * Devuelve la lista de IDs de canciones guardadas en caché para un evento
     */
    async getCachedSongIdsForEvent(eventId) {
        await this._initPromise;
        if (!this.db) return [];

        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const index = store.index('eventId');
            const req = index.getAll(String(eventId));

            req.onsuccess = (e) => {
                const records = e.target.result || [];
                resolve(records.map(r => String(r.id)));
            };

            req.onerror = () => {
                resolve([]);
            };
        });
    }

    /**
     * Calcula el espacio total en MB guardado en caché para un evento
     */
    async getEventStorageUsage(eventId) {
        await this._initPromise;
        if (!this.db) return { count: 0, bytes: 0, mb: '0.0' };

        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const index = store.index('eventId');
            const req = index.getAll(String(eventId));

            req.onsuccess = (e) => {
                const records = e.target.result || [];
                const totalBytes = records.reduce((acc, r) => acc + (r.size || 0), 0);
                resolve({
                    count: records.length,
                    bytes: totalBytes,
                    mb: (totalBytes / (1024 * 1024)).toFixed(1)
                });
            };

            req.onerror = () => {
                resolve({ count: 0, bytes: 0, mb: '0.0' });
            };
        });
    }

    /**
     * Elimina una canción individual de la caché
     */
    async deleteSong(songId) {
        await this._initPromise;
        if (!this.db) return;

        const sId = String(songId);
        if (this.activeBlobs.has(sId)) {
            URL.revokeObjectURL(this.activeBlobs.get(sId));
            this.activeBlobs.delete(sId);
        }

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const req = store.delete(sId);

            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e.target.error);
        });
    }

    /**
     * Elimina todas las canciones cacheadas de un evento (libera espacio en el móvil/tablet)
     */
    async deleteEventCache(eventId) {
        await this._initPromise;
        if (!this.db) return;

        const evId = String(eventId);

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const index = store.index('eventId');
            const req = index.getAll(evId);

            req.onsuccess = (e) => {
                const records = e.target.result || [];
                records.forEach(r => {
                    if (this.activeBlobs.has(String(r.id))) {
                        URL.revokeObjectURL(this.activeBlobs.get(String(r.id)));
                        this.activeBlobs.delete(String(r.id));
                    }
                    store.delete(r.id);
                });
                resolve({ deletedCount: records.length });
            };

            req.onerror = (e) => reject(e.target.error);
        });
    }

    /**
     * Descarga por lotes todas las canciones de un evento
     */
    async cacheAllEventSongs(songsList, eventId, onBatchProgress = null) {
        const downloadables = songsList.filter(s => !!(s.audio_file || s.audioFile || s.url));
        if (downloadables.length === 0) {
            throw new Error('No hay canciones con archivo o enlace de audio para descargar.');
        }

        let completed = 0;
        const total = downloadables.length;
        const results = [];

        for (let i = 0; i < total; i++) {
            const song = downloadables[i];
            try {
                if (onBatchProgress) {
                    onBatchProgress({
                        currentIndex: i + 1,
                        totalSongs: total,
                        currentSong: song,
                        percent: Math.round((i / total) * 100),
                        status: 'downloading'
                    });
                }
                const res = await this.cacheSong({ ...song, event_id: eventId });
                completed++;
                results.push({ songId: song.id, success: true, record: res });
            } catch (err) {
                console.warn(`Error descargando canción ${song.id} (${song.title}):`, err);
                results.push({ songId: song.id, success: false, error: err.message });
            }
        }

        if (onBatchProgress) {
            onBatchProgress({
                currentIndex: total,
                totalSongs: total,
                percent: 100,
                status: 'done',
                completed,
                total
            });
        }

        return { total, completed, results };
    }
}

// Instancia global disponible en toda la aplicación
window.OfflineAudioCache = new OfflineAudioCacheEngine();
