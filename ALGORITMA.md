# Algoritma Deteksi Anomali — Dokumentasi Teknis

Dokumen ini menjelaskan secara detail algoritma Machine Learning yang digunakan dalam sistem PDAM Anomaly Detection.

---

## 1. Isolation Forest — Detektor Utama

### 1.1 Konsep Dasar

Isolation Forest adalah algoritma unsupervised learning yang mendeteksi anomali berdasarkan **seberapa mudah** sebuah titik data "diisolasi" (dipisahkan) dari data lain.

> **Intuisi:** Data anomali cenderung "berbeda sendiri" sehingga butuh sedikit split untuk dipisahkan. Data normal tersebar padat dan butuh banyak split.

```
Normal Data                    Anomaly
    o  o                          A
  o  o  o  o                     o
    o    o  o                  o  o
      o  o  o                    o
```

### 1.2 Cara Kerja Algoritma

**Langkah 1: Bangun Pohon Isolasi (iTree)**

```
Prosedur BuildTree(X, h, h_limit):
    if h >= h_limit atau |X| <= 1:
        return Leaf(size = |X|)
    
    Pilih atribut q secara random (dari Rp, M3, Rp_per_M3)
    Pilih nilai split p secara random dari rentang min-max atribut q
    
    X_left  = {x ∈ X | x[q] < p}
    X_right = {x ∈ X | x[q] ≥ p}
    
    return Node(
        atribut = q,
        split   = p,
        kiri    = BuildTree(X_left,  h+1, h_limit),
        kanan   = BuildTree(X_right, h+1, h_limit)
    )
```

**Langkah 2: Hitung Path Length**

Path length = jumlah edge dari root sampai titik data diisolasi sebagai leaf.

| Data | Path Length | Interpretasi |
|------|-------------|--------------|
| Anomali | Pendek (2-3 split) | Mudah diisolasi |
| Normal | Panjang (8-10 split) | Butuh banyak split |

**Langkah 3: Hitung Anomaly Score**

```
s(x, n) = 2 ^ (- E(h(x)) / c(n))

s(x, n) = anomaly score (0 ~ 1)
E(h(x)) = rata-rata path length data x di semua pohon
c(n)    = normalisasi constant untuk n data
n       = jumlah sampel
```

| Score | Interpretasi |
|-------|--------------|
| s ≈ 1 | Sangat anomali |
| s ≈ 0.5 | Ambigu |
| s ≈ 0 | Normal |

### 1.3 Implementasi di Sistem

```python
@analyze.py:286-289

clf = IsolationForest(
    contamination=cont,      # proporsi anomali yang diharapkan
    random_state=42,         # reprodusibilitas
    n_estimators=100         # jumlah pohon (100 pohon)
)
clf.fit(X)                   # training pada data
labels = clf.predict(X)      # 1 = normal, -1 = anomali
scores = clf.score_samples(X) # negatif; makin negatif = makin anomali
```

**Fitur yang digunakan (X):**
```python
X = df[['Rp', 'M3', 'Rp_per_M3']].values
```

Algoritma bekerja secara **multivariate** — melihat kombinasi 3 atribut bersamaan, bukan satu per satu.

---

## 2. Contamination — Parameter Penting

Contamination = proporsi anomali yang diharapkan dalam dataset.

| Contamination | Arti | Gunakan Saat |
|---------------|------|-------------|
| `auto` | Model tentukan sendiri | Default — tidak tahu proporsi |
| `0.05` | 5% data dianggap anomali | Data relatif bersih |
| `0.10` | 10% data anomali | Ada beberapa ketidakwajaran |
| `0.20` | 20% data anomali | Banyak masalah data |
| `0.30` | 30% data anomali | Data sangat kotor |

**Validasi di sistem:**
```python
if contamination == 'auto':
    cont = 'auto'
else:
    cont = float(contamination)
    if cont <= 0 or cont > 0.5:  # batas aman
        cont = 'auto'
```

---

## 3. Grouping — Konteks Analisis

Isolation Forest dilatih pada **subset data** sesuai mode, bukan semua data global.

### Mode 1: near_tahun_per_golongan (DEFAULT)

```
Per Golongan × Per Tahun

Golongan S1, Tahun 2023 ──▶ iTree(S1-2023)
Golongan S1, Tahun 2024 ──▶ iTree(S1-2024)
Golongan S2, Tahun 2023 ──▶ iTree(S2-2023)
Golongan S2, Tahun 2024 ──▶ iTree(S2-2024)
```

**Kelebihan:** Paling akurat — membandingkan apel dengan apel (golongan & tahun sama)

**Kode:**
```python
for gol in all_golongan:
    for tahun in sorted(df['Tahun'].unique()):
        sub = df[(df['Golongan'] == gol) & (df['Tahun'] == tahun)]
        if len(sub) < 2: continue  # skip kalau data terlalu sedikit
        labels, scores = run_isolation_forest(sub, contamination)
```

### Mode 2: near_tahun_near_golongan

```
Semua data global ──▶ iTree(semua)
```

**Kelebihan:** Lihat anomali yang "unik" di seluruh dataset

### Mode 3: multi_tahun_per_golongan

```
Per Golongan (semua tahun digabung)

Golongan S1 (2021+2022+2023+2024) ──▶ iTree(S1)
Golongan S2 (2021+2022+2023+2024) ──▶ iTree(S2)
```

**Kelebihan:** Lihat tren jangka panjang per golongan

### Mode 4: multi_tahun_semua_golongan

```
Semua data (semua golongan, semua tahun) ──▶ iTree(ALL)
```

**Kelebihan:** Overview paling luas — lihat outlier global

---

## 4. Severity Level

**Bukan dari Z-Score!** Severity berasal dari ranking `anomaly_score` Isolation Forest.

### Rumus

```python
def assign_severity(scores_arr, is_anomaly_arr):
    # Hanya proses data yang sudah terdeteksi anomali (label = -1)
    anom_idx = np.where(is_anomaly_arr == 1)[0]
    anom_scores = scores_arr[anom_idx]
    
    # Score makin negatif = makin anomali
    # Ubah ke positif: makin besar = makin ekstrem
    neg = -anom_scores
    
    # Hitung percentile
    p80 = np.percentile(neg, 80)  # threshold top 20%
    p95 = np.percentile(neg, 95)  # threshold top 5%
    
    for setiap anomali:
        if neg >= p95:  severity = 'high'      # top 5% paling ekstrem
        elif neg >= p80: severity = 'medium'   # 15% berikutnya
        else:            severity = 'low'      # sisanya (80% anomali)
```

**Contoh perhitungan:**

| Data | Anomaly Score | -Score | Percentile | Severity |
|------|---------------|--------|-----------|----------|
| A | -0.85 | 0.85 | 99th | **HIGH** (≥ p95) |
| B | -0.72 | 0.72 | 91st | **MEDIUM** (≥ p80) |
| C | -0.65 | 0.65 | 87th | **MEDIUM** (≥ p80) |
| D | -0.55 | 0.55 | 75th | **LOW** (< p80) |
| E | -0.48 | 0.48 | 60th | **LOW** (< p80) |

---

## 5. Z-Score — Interpretasi Penyebab

### 5.1 Rumus

```
Z = (X - μ) / σ

X   = nilai data
μ   = rata-rata grup (golongan/tahun sesuai mode)
σ   = standar deviasi grup
|Z| = besar penyimpangan (absolut)
```

### 5.2 Kategori Z-Score

| |Z|| Interpretasi |
|------|-------------|
| |Z| < 1 | Normal (68% data)
| 1 ≤ |Z| < 2 | Sedikit menyimpang (27% data) |
| 2 ≤ |Z| < 3 | Cukup menyimpang (4.5% data) |
| |Z| ≥ 3 | Sangat menyimpang (0.3% data) |

### 5.3 Mengapa Ambil Top-2?

Karena tampilkan semua 3 atribut malah membingungkan user. Ambil **yang paling ekstrem** saja.

**Contoh:**

| Atribut | Nilai | μ | σ | Z | Rank |
|---------|-------|---|---|---|------|
| Rp | 185.000 | 150.000 | 10.000 | **+3.50** | 🥇 |
| Rp_per_M3 | 15.417 | 13.636 | 1.000 | **+1.78** | 🥈 |
| M3 | 12 | 11 | 2 | +0.50 | 🥉 |

**Top-2 yang ditampilkan:**
1. `Rp` — Tagihan jauh lebih tinggi (Z = +3.50)
2. `Rp_per_M3` — Tarif per m³ di atas normal (Z = +1.78)

`M3` tidak ditampilkan karena Z-nya kecil — tidak signifikan.

### 5.4 Combination Anomaly

Kadang semua Z kecil tapi IF tetap bilang anomali. Ini berarti anomali karena **kombinasi pola**.

**Contoh:**

| Atribut | Nilai | μ | σ | Z |
|---------|-------|---|---|---|
| Rp | 160.000 | 150.000 | 10.000 | +1.0 |
| M3 | 8 | 11 | 2 | -1.5 |
| Rp_per_M3 | 20.000 | 13.636 | 5.000 | +1.27 |

Semua Z < 2, tapi IF bilang anomali. Kenapa?

> Karena **M3 rendah (8) tapi Rp_per_M3 tinggi (20.000)** — ini kombinasi yang tidak wajar. Seharusnya kalau volume pemakaian rendah, tarif per m³ juga rendah.

```python
max_z = max(c['zscore'] for c in causes)  # 1.27
combination_note = True if max_z < 2.0    # iya, ini anomali kombinasi
```

---

## 6. Alur Kode Lengkap (Pseudocode)

```
function analyze(filepath, contamination, mode, tahun_range):
    
    # STEP 1: LOAD
    df = load_excel(filepath)
    df = rename_columns(df)
    df = clean_numeric(df)  # Rp, M3, Rp_per_M3 → float
    if tahun_range:
        df = filter_tahun(df, tahun_range)
    
    # STEP 2: INISIALISASI
    df['is_anomaly']    = 0
    df['anomaly_score'] = 0.0
    df['anomaly_level'] = 'normal'
    df['causes']        = null
    
    group_stats = {}  # untuk Z-Score nanti
    
    # STEP 3: GROUPING & ISOLATION FOREST
    if mode == 'near_tahun_per_golongan':
        for each (gol, tahun):
            sub = df[gol & tahun]
            if len(sub) < 2: continue
            
            # Hitung stats untuk Z-Score
            group_stats[(gol, tahun)] = {
                'Rp': {'mean': mean(sub.Rp), 'std': std(sub.Rp)},
                'M3': {'mean': mean(sub.M3), 'std': std(sub.M3)},
                'Rp_per_M3': {'mean': mean(sub.Rp_per_M3), 'std': std(sub.Rp_per_M3)}
            }
            
            # Isolation Forest
            labels, scores = run_isolation_forest(sub, contamination)
            is_anom = (labels == -1).astype(int)
            severity = assign_severity(scores, is_anom)
            
            df[sub.index, 'is_anomaly']    = is_anom
            df[sub.index, 'anomaly_score'] = scores
            df[sub.index, 'anomaly_level'] = severity
    
    # STEP 4: Z-SCORE CAUSES (per baris)
    for each row in df:
        if row.is_anomaly == 1:
            row['causes'] = compute_zscore_causes(row, group_stats)
    
    # STEP 5: BUILD OUTPUT
    result = {
        'meta': {
            'total': len(df),
            'anomali': sum(df.is_anomaly),
            'normal': sum(1 - df.is_anomaly),
            'pct_anomali': (anomali / total * 100),
            'mode': mode,
            'contamination': contamination,
            'tahun_range': [min, max],
            'severity': {high, medium, low},
            'insights': generate_insights(...)
        },
        'data': df[[kolom penting]],
        'summary_tahun': aggregasi per tahun,
        'summary_golongan': aggregasi per golongan
    }
    
    return result
```

---

## 7. Formula Ringkasan

| Komponen | Formula | Kegunaan |
|----------|---------|----------|
| Isolation Forest | `predict(X)` | Deteksi anomali (ya/tidak) |
| Anomaly Score | `score_samples(X)` | Ranking seberapa aneh |
| Severity | Percentile(-score, [80, 95]) | Klasifikasi High/Medium/Low |
| Z-Score | `\|X - μ\| / σ` | Interpretasi penyimpangan per atribut |
| Persentase Anomali | `(anomali / total) × 100%` | Ringkasan hasil |

---

## 8. Perbedaan IF vs Z-Score

| Aspek | Isolation Forest | Z-Score |
|-------|------------------|---------|
| **Input** | 3 atribut bersamaan (multivariate) | 1 atribut per hitung (univariate) |
| **Output** | Anomali / Normal | Seberapa nyimpang satu atribut |
| **Tujuan** | **DETEKSI** anomali | **INTERPRETASI** kenapa anomali |
| **Dipakai untuk** | Keputusan utama | Penjelasan user-friendly |
| **Contoh kasus** | "Data ini aneh" | "Karena Rp-nya terlalu tinggi" |

---

## 9. Keunggulan Isolation Forest

| Keunggulan | Penjelasan |
|------------|-----------|
| **Multivariate** | Lihat interaksi antar atribut (Rp, M3, Rp/m3) secara bersamaan |
| **Unsupervised** | Tidak perlu data yang sudah dilabel anomali/normal |
| **Robust** | Tahan terhadap outlier karena pakai struktur pohon random |
| **Skalabel** | Cepat untuk dataset besar (sublinear training time) |
| **Tidak butuh asumsi distribusi** | Berbeda dengan Z-Score yang asumsikan normal distribution |

---

## 10. Keterbatasan

| Keterbatasan | Dampak | Solusi di Sistem |
|--------------|--------|-----------------|
| Butuh data cukup | Kalau grup < 2 data, skip analisis | Validasi `len(sub) < 2: continue` |
| Sensitif contamination | Salah setting = terlalu banyak/terlalu sedikit anomali | Validasi 0 < cont ≤ 0.5 |
| Z-Score asumsikan normal distribution | Kalau data skewed, Z kurang akurat | Pakai |Z| ranking relatif saja |
| Tidak menjelaskan "kenapa" | IF hanya bilang ya/tidak | Z-Score sebagai penjelasan tambahan |
