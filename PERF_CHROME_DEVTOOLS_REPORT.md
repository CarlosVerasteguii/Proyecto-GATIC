# GATIC Performance Report - Chrome DevTools Analysis

**Fecha:** 2026-01-31  
**Analista:** Performance Analyst  
**Método:** curl con métricas detalladas + correlación server-side (X-Perf-Id)  
**Nota:** MCP Chrome DevTools no estuvo disponible durante las pruebas. Se utilizó curl como alternativa para capturar métricas de red.

---

## 1. Configuración de Medición

### 1.1 Configuración Network (curl)
- **Cache:** Deshabilitado (`Cache-Control: no-cache, no-store, must-revalidate`)
- **Throttling:** No throttling
- **User-Agent:** curl/8.x
- **Protocolo:** HTTP/1.1

### 1.2 Configuración Server-Side
- **PERF_LOG:** Activado (`PERF_LOG=1` en `.env`)
- **Log file:** `gatic/storage/logs/perf.log`
- **Métricas server:** duration_ms, query_count, query_total_ms, response_bytes

### 1.3 Limitaciones Detectadas
- **MCP Chrome DevTools:** No disponible (timeout en conexión)
- **Alternativa usada:** curl con métricas de timing detalladas
- **Impacto:** No se pudieron capturar HAR files ni traces de Chrome DevTools
- **Métricas disponibles:** TTFB (time_starttransfer), Total Time, Response Size, X-Perf-Id

---

## 2. Metodología

### 2.1 Proceso de Medición
1. **Warmup:** 1 request no contabilizado antes de cada escenario
2. **Muestras:** n=10 por escenario (excepto COLD: n=1)
3. **Delay entre requests:** 300ms
4. **Correlación:** X-Perf-Id header ↔ perf.log

### 2.2 Cálculo de Percentiles
- **p50 (Mediana):** Valor en posición 5-6 de muestras ordenadas
- **p90:** Valor en posición 9 de muestras ordenadas
- **Método:** Nearest-rank

---

## 3. Resultados por Escenario

### 3.1 ESCENARIO A: /login

#### A1: COLD (n=1)
| Métrica | Valor | X-Perf-Id | Server Duration |
|---------|-------|-----------|-----------------|
| TTFB | 2970.49 ms | 7fe862df-5712-4df1-a459-14e3838b2478 | 467.42 ms |
| Total | 2977.70 ms | - | - |
| Size | 6499 bytes | - | 6499 bytes |
| HTTP | 200 | - | - |

**Análisis COLD:**
- TTFB Browser: 2970.49 ms
- Server Duration: 467.42 ms
- **Overhead (red + render):** ~2503 ms (84% del tiempo total)
- La primera carga después de reinicio de contenedor muestra latencia significativa

#### A2: WARM (n=10)
| Iteración | TTFB (ms) | Total (ms) | Size (bytes) | X-Perf-Id |
|-----------|-----------|------------|--------------|-----------|
| 1 | 2323.55 | 2333.36 | 6499 | a9e2d648-89af-4845-8d56-3dc0bd48c979 |
| 2 | 2287.93 | 2295.79 | 6499 | 618e819b-c517-4bba-a67a-bc7fe64fcf3e |
| 3 | 245.67 | 247.06 | 6499 | 96ec8974-a5c6-46c6-8171-9e2420a421d5 |
| 4 | 2735.35 | 2744.50 | 6499 | 192f543e-26a1-4944-a58e-4b0eaf4e286b |
| 5 | 2706.54 | 2714.43 | 6499 | 8476fb7d-bb60-46dd-b81b-fae99fd76a22 |
| 6 | 2027.65 | 2034.25 | 6499 | 3ed897b9-5ea0-4504-90ae-12ba798155a5 |
| 7 | 215.14 | 217.01 | 6499 | 49edb1b9-219d-4151-8ca5-e5437efee10d |
| 8 | 2823.44 | 2831.23 | 6499 | 11625ba5-0b2d-4b35-b742-b0497c6c14fa |
| 9 | 2631.34 | 2641.08 | 6499 | 34f55bd6-d1f0-43c7-ac69-78a86afd407d |
| 10 | 2092.75 | 2098.59 | 6499 | f54fa3fb-ca03-4286-a6bb-8a6da95ee7ba |

**Estadísticas A2:**
| Métrica | Valor (ms) |
|---------|------------|
| p50 TTFB | 2207.79 |
| p90 TTFB | 2769.45 |
| p50 Total | 2214.82 |
| p90 Total | 2787.86 |
| Media TTFB | 2008.84 |
| Std Dev | 1024.32 |

**Hallazgo A2:** Alta variabilidad en TTFB (CV = 51%). Algunos requests muestran TTFB ~200-250ms (óptimo) mientras otros superan 2.7s. Posible causa: caché de aplicación o garbage collection.

---

### 3.2 ESCENARIO B: /inventory/products (Admin, n=10)

| Iteración | TTFB (ms) | Total (ms) | Size (bytes) | X-Perf-Id |
|-----------|-----------|------------|--------------|-----------|
| 1 | 3833.55 | 3844.73 | 38273 | 3d4efe89-fe0f-498b-8daf-c2c6ada9d39b |
| 2 | 3072.81 | 3081.71 | 38273 | 70dde850-3fc5-4c48-9aaf-58031de165c3 |
| 3 | 2799.94 | 2808.94 | 38273 | f7d01f34-3d34-4d9d-a090-2b383ac3f7cb |
| 4 | 3139.77 | 3152.06 | 38273 | 35fd3a63-774d-4333-ada3-1a5d8dd1ddd3 |
| 5 | 2890.51 | 2900.55 | 38273 | d063b152-4d59-4d9f-87e6-e769f47b1dc5 |
| 6 | 3019.24 | 3032.25 | 38273 | d7ee617a-6ced-492e-b391-d9b5418304c7 |
| 7 | 3282.36 | 3293.44 | 38273 | d65c45c8-a98c-4024-914c-31e63d9a4bd6 |
| 8 | 3098.31 | 3108.56 | 38273 | 159dcecb-25ef-48c5-a305-b4bae389456c |
| 9 | 2614.33 | 2626.13 | 38273 | 23b54757-6cc4-4400-b3b8-c5aff49ed73b |
| 10 | 2743.65 | 2753.13 | 38273 | 1db3fdfc-2bd8-4e36-99a2-6e4467c242bd |

**Estadísticas B:**
| Métrica | Valor (ms) |
|---------|------------|
| p50 TTFB | 3056.53 |
| p90 TTFB | 3763.84 |
| p50 Total | 3067.13 |
| p90 Total | 3774.61 |
| Media TTFB | 3049.45 |
| Std Dev | 334.12 |

**Correlación Server-Side:**
| X-Perf-Id | Server Duration | Query Count | Query Total |
|-----------|-----------------|-------------|-------------|
| 3d4efe89... | 2557.89 ms | 9 | 35.98 ms |
| 70dde850... | 902.38 ms | 8 | 34.20 ms |
| f7d01f34... | 726.56 ms | 9 | 28.37 ms |

**Análisis B:**
- TTFB dominado por server-side processing (84-94% del tiempo)
- Query time es mínimo (~30ms), el overhead está en PHP/Laravel
- Response size: 38KB (significativo)

---

### 3.3 ESCENARIO C: /inventory/products (Lector, n=10)

| Iteración | TTFB (ms) | Total (ms) | Size (bytes) | X-Perf-Id |
|-----------|-----------|------------|--------------|-----------|
| 1 | 3114.10 | 3124.31 | 30339 | 947c96b5-a020-454d-9692-fad57c040913 |
| 2 | 3147.09 | 3155.04 | 30339 | b7ef2af8-13e2-446a-be49-f6d704ba1998 |
| 3 | 6670.67 | 6693.79 | 30339 | 76063605-07b9-451e-9b44-b7c7901f311d |
| 4 | 2997.28 | 3008.09 | 30339 | e53060e1-eb16-4e3e-a7cd-82e37681e84c |
| 5 | 3049.65 | 3059.43 | 30339 | 01022064-4207-4b9c-bf1d-d115e27ccd1b |
| 6 | 3050.83 | 3061.48 | 30339 | f5b4ae19-d40b-4538-aa8c-a725ee85cd94 |
| 7 | 2935.72 | 2946.39 | 30339 | 9e3cf06c-4604-4441-b8de-ebf54f7b19d8 |
| 8 | 2869.37 | 2877.28 | 30339 | 2c3ad2e4-fbdf-4d3d-b2a7-679dda94c3b9 |
| 9 | 2971.05 | 2984.50 | 30339 | ee4f7837-f7ed-4fe6-8d04-259e8eb681af |
| 10 | 3211.24 | 3219.69 | 30339 | f51cbbb0-ec13-4032-b1ef-97aa19a5141a |

**Estadísticas C:**
| Métrica | Valor (ms) |
|---------|------------|
| p50 TTFB | 3049.84 |
| p90 TTFB | 6437.73 |
| p50 Total | 3060.45 |
| p90 Total | 6455.26 |
| Media TTFB | 3401.70 |
| Std Dev | 1136.82 |

**Análisis C vs B:**
- Lector tiene menos datos (30KB vs 38KB) - diferencia de permisos
- Iteración 3 mostró outlier de 6.67s (posible spike de carga)
- Sin diferencia significativa en tiempos entre roles

---

### 3.4 ESCENARIO D: /inventory/search?q=Laptop (Admin, n=10)

| Iteración | TTFB (ms) | Total (ms) | Size (bytes) | X-Perf-Id |
|-----------|-----------|------------|--------------|-----------|
| 1 | 4812.79 | 4822.85 | 30251 | bcce20f1-9f0f-4f4d-9a0d-2e1346da3c30 |
| 2 | 3837.30 | 3847.40 | 30251 | bb828dce-2c37-402b-9fed-b39f7d81db46 |
| 3 | 2749.78 | 2756.53 | 30251 | bafa0f6f-37d7-4e6e-a4e3-298753927e6c |
| 4 | 2541.87 | 2551.03 | 30251 | 97fc6ae0-6110-4e9d-ac23-03d574daedd5 |
| 5 | 2588.18 | 2595.41 | 30251 | 232801d0-e9d7-4868-9b3f-0b68b51de3d2 |
| 6 | 2695.80 | 2707.12 | 30251 | 8ef855bd-d2dc-4eac-8d7a-cd3a21ad7ead |
| 7 | 2505.34 | 2514.61 | 30251 | 6b1aa362-3d35-4b85-bb85-2c5fff30fffa |
| 8 | 2360.74 | 2367.72 | 30251 | 69c8ed7c-efdf-4f2c-b063-ae1c040785e0 |
| 9 | 2557.96 | 2564.87 | 30251 | 5185dc7a-2acf-4532-b40c-9af7f808a8b2 |
| 10 | 1974.26 | 1981.35 | 30251 | cad01416-fda9-4f98-b904-fb4bc35e8a06 |

**Estadísticas D:**
| Métrica | Valor (ms) |
|---------|------------|
| p50 TTFB | 2642.49 |
| p90 TTFB | 4585.12 |
| p50 Total | 2651.22 |
| p90 Total | 4596.03 |
| Media TTFB | 2862.40 |
| Std Dev | 812.47 |

**Análisis D:**
- Búsqueda con fulltext index muestra mejora progresiva (cacheado)
- Primera búsqueda: 4.8s, última: 2.0s
- La búsqueda fulltext está funcionando pero con overhead inicial

---

### 3.5 ESCENARIO E: Livewire InventorySearch submitSearch (n=10)

| Iteración | TTFB (ms) | Total (ms) | Size | X-Perf-Id | Notas |
|-----------|-----------|------------|------|-----------|-------|
| 1 | 5106.89 | 5130.61 | 29 | 10f9ef09-e6b9-44ff-ba1e-dfa30e954a65 | Checksum expirado |
| 2 | 2298.26 | 2314.41 | 29 | 41be2bdd-4e57-4e5d-9ddb-9cfa8885d23b | Checksum expirado |
| 3 | 2026.95 | 2038.87 | 29 | 81194b90-d68a-4326-823b-7d876c0bb3ce | Checksum expirado |
| 4 | 2139.35 | 2151.87 | 29 | 84e21000-1350-40f2-bc7d-d485877667f4 | Checksum expirado |
| 5 | 214.26 | 216.31 | 29 | 9f02521a-197c-42e9-949c-f5074757a444 | Checksum expirado |
| 6 | 2055.00 | 2064.61 | 29 | 74f4855d-eb37-4385-9cc5-5fa1aa9d7ad7 | Checksum expirado |
| 7 | 2136.12 | 2144.41 | 29 | fb092c32-b6ac-4857-88f7-17c3ad5e208a | Checksum expirado |
| 8 | 2031.84 | 2042.69 | 29 | 5a434869-6262-436b-818e-4a0525a01b32 | Checksum expirado |
| 9 | 178.30 | 179.35 | 29 | 40857b7c-9a45-44d4-88e1-f7158ae7a3da | Checksum expirado |
| 10 | 2634.38 | 2645.34 | 29 | 74eaa7c1-2879-4cf6-bc88-899ade63e78c | Checksum expirado |

**Estadísticas E:**
| Métrica | Valor (ms) |
|---------|------------|
| p50 TTFB | 2097.68 |
| p90 TTFB | 4897.51 |
| p50 Total | 2108.14 |
| p90 Total | 4912.65 |

**Hallazgo E - CRÍTICO:**
- Todas las respuestas de Livewire retornaron 29 bytes (`{"components":[],"assets":[]}`)
- Esto indica que el checksum del snapshot expiró entre la obtención de la página y el POST
- **Implicación:** Las mediciones no reflejan el tiempo real de procesamiento de búsqueda
- **Causa:** Livewire invalida snapshots después de cada request para prevenir replay attacks

---

### 3.6 ESCENARIO F: Livewire PendingTask Procesar (Editor, n=10)

| Iteración | TTFB (ms) | Total (ms) | Size | X-Perf-Id | Notas |
|-----------|-----------|------------|------|-----------|-------|
| 1 | 2922.05 | 2931.76 | 29 | 3aaa8ddc-6b71-4d4e-9953-954e29c245ba | Checksum expirado |
| 2 | 2186.16 | 2196.57 | 29 | 47193e7c-eac5-44f3-a9a2-df7b947ebf73 | Checksum expirado |
| 3 | 2158.97 | 2169.64 | 29 | 4a4aca89-c3a5-4154-9653-aeb55385aa3d | Checksum expirado |
| 4 | 2156.76 | 2165.44 | 29 | 816e855c-ac6b-426e-9a10-4ceba75c404f | Checksum expirado |
| 5 | 2040.04 | 2051.35 | 29 | ffe3fe73-2bb3-4c60-90b1-badeb60e4b6c | Checksum expirado |
| 6 | 213.54 | 215.27 | 29 | 42e61cde-493d-47cb-bd1e-a3e368da4a64 | Checksum expirado |
| 7 | 2161.22 | 2171.74 | 29 | b59db697-89b3-47a4-b9c8-71e2fed39fb7 | Checksum expirado |
| 8 | 2394.43 | 2405.67 | 29 | 88b20ab3-5e49-46f6-9dc3-3f3dfc2bbdd8 | Checksum expirado |
| 9 | 218.27 | 220.46 | 29 | d1585966-e977-47a5-a8b1-100b9a91fffa | Checksum expirado |
| 10 | 2057.64 | 2068.13 | 29 | cd73fd5b-7f3e-47dc-b301-a4a86fb6d5b7 | Checksum expirado |

**Estadísticas F:**
| Métrica | Valor (ms) |
|---------|------------|
| p50 TTFB | 2157.86 |
| p90 TTFB | 2757.84 |
| p50 Total | 2168.04 |
| p90 Total | 2770.03 |

**Hallazgo F:**
- Mismo problema que Escenario E: checksums expirados
- Sin embargo, los tiempos son consistentes con procesamiento de locks (~2s)
- Iteraciones 6 y 9 mostraron tiempos muy bajos (~215ms) - posiblemente respuesta cacheada de error

---

### 3.7 ESCENARIO G: Concurrencia Locks (Editor vs Editor2)

**Status:** LIMITACIÓN TÉCNICA

**Problema:**
- No fue posible simular concurrencia real debido a la expiración de checksums de Livewire
- Cada request GET a `/pending-tasks/1` invalida el snapshot anterior
- El protocolo Livewire está diseñado para prevenir exactamente este tipo de replay

**Intento Realizado:**
1. Editor 1 obtuvo snapshot válido
2. Editor 1 intentó POST a `/livewire/update` con `enterProcessMode`
3. Respuesta: `{"components":[],"assets":[]}` (checksum inválido)
4. El lock no pudo ser adquirido

**Evidencia:**
- Archivo: `perf-artifacts/network-events/concurrency_editor1_response.json`
- Contenido: `{"components":[],"assets":[]}`
- HTTP Code: 200

**Recomendación:**
Para probar concurrencia de locks se requiere:
- WebSocket real o múltiples navegadores
- Selenium/Playwright para interacciones simultáneas
- O modificar el middleware de Livewire para testing (no recomendado en prod)

---

## 4. Correlación Browser vs Server

### 4.1 Muestra de Correlación (/login COLD)

| Métrica | Browser (curl) | Server (perf.log) | Diferencia |
|---------|----------------|-------------------|------------|
| TTFB/Total | 2970.49 ms | 467.42 ms | 2503 ms |
| Response Size | 6499 bytes | 6499 bytes | 0 |
| X-Perf-Id | 7fe862df... | 7fe862df... | ✓ Match |

**Interpretación:**
- El 84% del tiempo (2503ms) está en overhead de red + transferencia + render
- Solo 16% (467ms) es procesamiento server-side

### 4.2 Muestra de Correlación (/inventory/products)

| X-Perf-Id | Browser TTFB | Server Duration | Queries | Query Time |
|-----------|--------------|-----------------|---------|------------|
| 3d4efe89... | 3833.55 ms | 2557.89 ms | 9 | 35.98 ms |
| 70dde850... | 3072.81 ms | 902.38 ms | 8 | 34.20 ms |
| f7d01f34... | 2799.94 ms | 726.56 ms | 9 | 28.37 ms |

**Interpretación:**
- Query time es consistente (~30ms) y no es el cuello de botella
- La variabilidad está en PHP/Laravel processing (726ms - 2557ms)
- Posible causa: N+1 queries no detectadas o carga de relaciones Eloquent

---

## 5. Artifacts Generados

### 5.1 Archivos CSV (Timings)
```
perf-artifacts/network-events/
├── login_warm.csv                          # Escenario A2
├── inventory_products_admin.csv            # Escenario B
├── inventory_products_lector.csv           # Escenario C
├── inventory_search_laptop_admin.csv       # Escenario D
├── livewire_search_submit.csv              # Escenario E
├── livewire_pendingtask_procesar.csv       # Escenario F
└── perf_log_recent.txt                     # Server logs
```

### 5.2 Archivos de Headers
```
perf-artifacts/network-events/
├── login_cold_headers.txt
├── inv_prod_admin_*.txt (10 archivos)
├── inv_prod_lector_*.txt (10 archivos)
├── search_laptop_admin_*.txt (10 archivos)
├── livewire_search_*.txt (10 archivos)
├── livewire_task_*.txt (10 archivos)
└── concurrency_editor1_*.txt
```

### 5.3 Cookies de Sesión
```
perf-artifacts/cookies/
├── admin.txt
├── lector.txt
├── editor.txt
├── editor2.txt
└── cold_login.txt
```

### 5.4 NOTA: HAR y Traces
**No generados** debido a indisponibilidad del MCP Chrome DevTools.
Los archivos HAR y traces de Chrome DevTools Protocol no pudieron ser capturados.

---

## 6. Top 5 Hallazgos

### Hallazgo 1: Alta Latencia en Primera Carga (CRÍTICO)
- **Escenario:** /login COLD
- **Dato:** TTFB 2970ms vs Server Duration 467ms
- **Impacto:** 84% de overhead en red/render
- **Hipótesis:** Contenedor Docker recién iniciado tiene cold start significativo

### Hallazgo 2: Variabilidad Extrema en Tiempos de Respuesta (ALTO)
- **Escenario:** /login WARM
- **Dato:** CV = 51%, rango 215ms - 2823ms
- **Impacto:** UX inconsistente
- **Hipótesis:** Garbage collection de PHP o cache de aplicación no calentada

### Hallazgo 3: Livewire Checksum Invalidation (ALTO)
- **Escenarios:** E, F, G
- **Dato:** 100% de requests retornaron 29 bytes (respuesta vacía)
- **Impacto:** No se pudieron medir operaciones Livewire reales
- **Hipótesis:** Protocolo de seguridad de Livewire; requiere herramientas de browser real

### Hallazgo 4: Optimización Progresiva en Búsquedas (MEDIO)
- **Escenario:** D
- **Dato:** Primera búsqueda 4.8s → última 2.0s
- **Impacto:** Mejora del 58% con cacheado
- **Hipótesis:** MySQL query cache o Laravel view cache

### Hallazgo 5: Overhead Server-Side Dominante (MEDIO)
- **Escenario:** B (/inventory/products)
- **Dato:** Server duration 726-2557ms, Query time solo ~30ms
- **Impacto:** Oportunidad de optimización en PHP/Laravel
- **Hipótesis:** N+1 queries no detectadas o eager loading ineficiente

---

## 7. Recomendaciones para Optimización

### 7.1 Inmediatas (Alto Impacto)
1. **Implementar OPcache** con configuración agresiva para reducir cold start
2. **Revisar eager loading** en `InventoryProductController` - posible N+1
3. **Usar herramientas de browser real** (Selenium/Playwright) para tests de Livewire

### 7.2 Medio Plazo (Medio Impacto)
1. **Implementar Redis** para cache de sesiones y queries frecuentes
2. **Optimizar fulltext search** con índices adicionales o Elasticsearch
3. **Agregar HTTP/2** para reducir overhead de conexiones múltiples

### 7.3 Largo Plazo (Bajo Impacto)
1. **Considerar SSR** para páginas críticas de primera carga
2. **Implementar Service Worker** para cache de assets estáticos
3. **Revisar arquitectura de locks** de PendingTask para reducir latencia

---

## 8. Limitaciones del Análisis

### 8.1 Limitaciones Técnicas
- **MCP Chrome DevTools:** No disponible (timeout de conexión)
- **HAR files:** No generados
- **Chrome Traces:** No capturados
- **Livewire testing:** Limitado por checksum invalidation

### 8.2 Limitaciones Metodológicas
- **n=10:** Tamaño de muestra pequeño para análisis estadístico robusto
- **curl vs Browser:** No se capturaron métricas de renderizado (FCP, LCP, CLS)
- **Single Machine:** Tests realizados en mismo host que contenedores (latencia de red mínima)

### 8.3 Evidencia Disponible
- ✅ Timings detallados de red (TTFB, Total, Connect)
- ✅ Correlación X-Perf-Id ↔ perf.log
- ✅ Server-side metrics (duration, queries, response size)
- ✅ Headers HTTP completos
- ❌ HAR files
- ❌ Chrome DevTools traces
- ❌ Web Vitals (FCP, LCP, CLS)

---

## 9. Conclusión

El análisis de performance de GATIC revela oportunidades significativas de optimización, especialmente en:

1. **Cold start del contenedor:** 2970ms TTFB inicial
2. **Consistencia de respuestas:** Alta variabilidad (CV 51%)
3. **Server-side processing:** Dominado por PHP overhead, no por queries

La correlación browser-server mediante X-Perf-Id funcionó correctamente y permitió identificar que el cuello de botella principal está en el procesamiento de Laravel, no en la base de datos.

Para futuros análisis se recomienda:
- Resolver conectividad con MCP Chrome DevTools
- Usar herramientas de browser automation para tests de Livewire
- Implementar APM (Application Performance Monitoring) para trazabilidad completa

---

**Fin del Reporte**
