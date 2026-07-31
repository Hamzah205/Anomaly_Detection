#!/usr/bin/env python3
"""
PDAM Anomaly Detection System
Isolation Forest + Z-Score + Severity Level (Low/Medium/High)
v2.0
"""

import sys, json, os
import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
import warnings

class NumpyEncoder(json.JSONEncoder):
    """Handle semua tipe numpy/bool agar JSON serializable."""
    def default(self, obj):
        if isinstance(obj, (np.integer,)):   return int(obj)
        if isinstance(obj, (np.floating,)):  return float(obj)
        if isinstance(obj, (np.bool_,)):     return int(obj)
        if isinstance(obj, np.ndarray):      return obj.tolist()
        if isinstance(obj, bool):            return int(obj)
        return super().default(obj)


warnings.filterwarnings('ignore')

BULAN_ORDER = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
               'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER']
BULAN_NUM = {b: i+1 for i, b in enumerate(BULAN_ORDER)}

ATTR_LABELS = {'Rp': 'Rp', 'M3': 'm3', 'Rp_per_M3': 'Rp/m3'}
ATTR_NAMES  = {'Rp': 'nilai pembayaran (Rp)',
               'M3': 'volume pemakaian air (m\u00b3)',
               'Rp_per_M3': 'tarif per meter kubik (Rp/m\u00b3)'}

# ─────────────────────────────────────────
# 1. LOAD & CLEAN DATA
# ─────────────────────────────────────────
def load_data(filepath):
    """Load data dari Excel file"""
    df = pd.read_excel(filepath)
    df.columns = [c.strip() for c in df.columns]
    col_map = {}
    for c in df.columns:
        cl = c.lower().strip()
        if   cl == 'tahun':                          col_map[c] = 'Tahun'
        elif cl == 'bulan':                          col_map[c] = 'Bulan'
        elif 'nama' in cl and 'gol' in cl:           col_map[c] = 'NamaGolongan'
        elif cl == 'golongan':                       col_map[c] = 'Golongan'
        elif cl == 'rp' or c.upper() == 'RP':        col_map[c] = 'Rp'
        elif cl == 'm3':                             col_map[c] = 'M3'
        elif cl == 'rp_per_m3' or 'rp/m3' in cl:    col_map[c] = 'Rp_per_M3'
    df = df.rename(columns=col_map)
    df['BulanNum']  = df['Bulan'].map(BULAN_NUM).fillna(0).astype(int)
    df['Tahun']     = df['Tahun'].astype(int)
    df['Rp']        = pd.to_numeric(df['Rp'],        errors='coerce').fillna(0)
    df['M3']        = pd.to_numeric(df['M3'],        errors='coerce').fillna(0)
    df['Rp_per_M3'] = pd.to_numeric(df['Rp_per_M3'], errors='coerce').fillna(0)
    return df

def load_data_json(json_path_or_stdin='stdin'):
    """Load data dari JSON format (database) - via stdin atau file"""
    if json_path_or_stdin == 'stdin':
        raw = sys.stdin.read()
    else:
        with open(json_path_or_stdin, 'r', encoding='utf-8') as f:
            raw = f.read()
    
    data = json.loads(raw)
    records = data if isinstance(data, list) else data.get('data', [])
    
    # Convert ke DataFrame dengan kolom yang sama seperti Excel
    df = pd.DataFrame(records)
    
    # Rename kolom ke format standar
    col_map = {}
    for c in df.columns:
        cl = str(c).lower().strip()
        if   cl == 'tahun':                          col_map[c] = 'Tahun'
        elif cl == 'bulan':                          col_map[c] = 'Bulan'
        elif 'nama' in cl and 'gol' in cl:           col_map[c] = 'NamaGolongan'
        elif cl == 'golongan':                       col_map[c] = 'Golongan'
        elif cl == 'rp' or str(c).upper() == 'RP':   col_map[c] = 'Rp'
        elif cl == 'm3':                             col_map[c] = 'M3'
        elif 'rp' in cl and 'm3' in cl:             col_map[c] = 'Rp_per_M3'
    df = df.rename(columns=col_map)
    
    # Pastikan kolom ada
    if 'BulanNum' not in df.columns and 'Bulan' in df.columns:
        df['BulanNum'] = df['Bulan'].map(BULAN_NUM).fillna(0).astype(int)
    if 'BulanNum' in df.columns:
        df['BulanNum'] = df['BulanNum'].fillna(0).astype(int)
    
    df['Tahun'] = pd.to_numeric(df['Tahun'], errors='coerce').fillna(0).astype(int)
    df['Rp'] = pd.to_numeric(df['Rp'], errors='coerce').fillna(0)
    df['M3'] = pd.to_numeric(df['M3'], errors='coerce').fillna(0)
    df['Rp_per_M3'] = pd.to_numeric(df['Rp_per_M3'], errors='coerce').fillna(0)
    
    return df


# ─────────────────────────────────────────
# 2. SEVERITY LEVEL dari anomaly_score
#    score dari score_samples(): lebih negatif = lebih anomalous
#    Kita normalisasi ke rentang 0–1 lalu klasifikasi
# ─────────────────────────────────────────
def assign_severity(scores_arr, is_anomaly_arr):
    """
    PERBAIKAN: Severity percentile-based (5% HIGH, 15% MEDIUM, sisanya LOW)
    Hanya untuk baris yang sudah terdeteksi anomali oleh Isolation Forest.
    Score negatif = lebih anomali (dipakai untuk ranking).
    """
    levels = np.array(['normal'] * len(scores_arr), dtype=object)
    anom_idx = np.where(is_anomaly_arr == 1)[0]
    if len(anom_idx) == 0:
        return levels

    anom_scores = scores_arr[anom_idx]  # negatif = lebih anomali
    # Ranking ascending: score lebih negatif = rank lebih kecil = lebih ekstrem
    neg = -anom_scores  # positif = lebih ekstrem
    
    # 5% paling ekstrem (neg tertinggi) → HIGH, 15% berikutnya → MEDIUM, sisanya → LOW
    p80 = np.percentile(neg, 80)  # threshold top 20%
    p95 = np.percentile(neg, 95)  # threshold top 5%

    for i, idx in enumerate(anom_idx):
        v = neg[i]
        if   v >= p95:  levels[idx] = 'high'    # 5% paling ekstrem (neg tertinggi)
        elif v >= p80:  levels[idx] = 'medium' # 15% berikutnya
        else:           levels[idx] = 'low'   # sisanya
    return levels


# ─────────────────────────────────────────
# 3. Z-SCORE CAUSES — lebih kaya informasi
# ─────────────────────────────────────────
def build_cause(attr, raw_z, value, mean, std, context='grup'):
    """Bangun objek cause dengan penjelasan user-friendly + teknis."""
    label      = ATTR_LABELS[attr]
    attr_name  = ATTR_NAMES[attr]
    z_signed   = round((value - mean) / std, 3) if std > 0 else 0
    z_abs      = abs(z_signed)
    direction  = 'di atas' if z_signed > 0 else 'di bawah'
    dir_word   = 'lebih tinggi' if z_signed > 0 else 'lebih rendah'

    # Kategorisasi zscore
    if z_abs >= 3:
        sev_z = 'tinggi'
        sev_note = 'jauh menyimpang'
    elif z_abs >= 2:
        sev_z = 'sedang'
        sev_note = 'cukup menyimpang'
    else:
        sev_z = 'ringan'
        sev_note = 'sedikit menyimpang'

    # Penjelasan user-friendly
    if attr == 'Rp':
        if z_signed > 0:
            friendly = (f"Nilai {attr_name} jauh lebih tinggi dibandingkan rata-rata "
                        f"normal pada golongan ini. Hal ini mengindikasikan kemungkinan "
                        f"ketidakwajaran dalam tagihan atau lonjakan pemakaian yang tidak biasa.")
        else:
            friendly = (f"Nilai {attr_name} jauh lebih rendah dari rata-rata golongan ini. "
                        f"Bisa mengindikasikan kesalahan pencatatan atau potongan yang tidak wajar.")
    elif attr == 'M3':
        if z_signed > 0:
            friendly = (f"Volume pemakaian air ({label}) sangat tinggi dibanding pola normal. "
                        f"Kemungkinan ada kebocoran, lonjakan pemakaian, atau kesalahan pembacaan meter.")
        else:
            friendly = (f"Volume pemakaian air ({label}) sangat rendah dibanding pola normal. "
                        f"Bisa mengindikasikan meter tidak berfungsi, pelanggan tidak aktif, atau kesalahan data.")
    else:  # Rp_per_M3
        if z_signed > 0:
            friendly = (f"Tarif per meter kubik ({label}) jauh lebih tinggi dari rata-rata. "
                        f"Dapat mengindikasikan ketidaksesuaian penerapan tarif atau kesalahan perhitungan.")
        else:
            friendly = (f"Tarif per meter kubik ({label}) jauh lebih rendah dari biasanya. "
                        f"Dapat mengindikasikan ketidaksesuaian tarif atau kesalahan pencatatan data.")

    # Penjelasan teknis
    technical = (f"Nilai {label} berada {direction} rata-rata dengan penyimpangan {sev_note} "
                 f"(Z = (X \u2212 \u03bc) / \u03c3 = ({value:,.2f} \u2212 {mean:,.2f}) / {std:,.2f} = {z_signed:+.3f}). "
                 f"|Z| = {z_abs:.3f} \u2192 dikategorikan sebagai anomali {sev_z}.")

    return {
        'atribut'    : label,
        'attr_full'  : attr_name,
        'zscore'     : z_abs,
        'zscore_raw' : z_signed,
        'nilai'      : round(float(value), 2),
        'rata_rata'  : round(float(mean),  2),
        'std'        : round(float(std),   2),
        'arah'       : direction,
        'severity_z' : sev_z,
        'penjelasan' : friendly,
        'teknis'     : technical,
        # keterangan singkat (compat lama)
        'keterangan' : f"Nilai {label} {dir_word} rata-rata ({context}) — z={z_signed:+.3f}"
    }


def compute_zscore_causes(row, group_stats):
    """
    Hitung Z-Score untuk semua atribut, ambil top-2 berdasarkan |Z| terbesar.
    Z-Score TIDAK digunakan untuk menentukan anomali — itu tugas Isolation Forest.
    Z-Score hanya untuk INTERPRETASI: atribut mana yang paling menyimpang.

    Aturan:
    - Selalu kembalikan top-2 atribut (atau kurang jika std=0)
    - Jika semua |Z| < 2.0, tambahkan combination_note = True
      (artinya anomali terjadi karena kombinasi, bukan satu atribut ekstrem)
    - TIDAK ADA threshold filter untuk menyaring/membuang atribut
    """
    gol   = row['Golongan']
    tahun = row['Tahun']
    candidates = []

    # Cari group stats yang paling spesifik tersedia
    # Urutan prioritas: (gol+tahun) → (gol) → (tahun) → (ALL)
    key = (gol, tahun)
    if key not in group_stats:
        key = (gol,)
        if key not in group_stats:
            key = (tahun,)           # Mode 2: near_tahun_near_golongan
            if key not in group_stats:
                key = ('ALL',)
                if key not in group_stats:
                    return []

    gs = group_stats[key]
    # Tentukan label konteks berdasarkan bentuk key
    if len(key) == 2:
        context = 'golongan & tahun'
    elif key[0] == 'ALL':
        context = 'global'
    elif isinstance(key[0], int):
        context = 'tahun'            # key = (tahun,) dari Mode 2
    else:
        context = 'golongan'

    # Hitung Z-Score untuk semua atribut — TANPA threshold filter
    for attr in ['Rp', 'M3', 'Rp_per_M3']:
        if attr not in gs or gs[attr]['std'] <= 0:
            continue
        mean, std = gs[attr]['mean'], gs[attr]['std']
        val   = row[attr]
        z_abs = abs((val - mean) / std)
        candidates.append((z_abs, attr, mean, std, context))

    # Urutkan descending berdasarkan |Z|
    candidates.sort(key=lambda x: x[0], reverse=True)

    # Ambil top-2 (selalu, tidak ada threshold cutoff)
    top2 = candidates[:2]

    causes = [
        build_cause(attr, z_abs, row[attr], mean, std, ctx)
        for z_abs, attr, mean, std, ctx in top2
    ]

    # Tandai jika semua Z kecil — anomali karena kombinasi pola, bukan satu atribut ekstrem
    # Threshold label (bukan filter): z < 2.0 dianggap tidak ada atribut yang sangat menonjol
    max_z = max((c['zscore'] for c in causes), default=0)
    combination_anomaly = 1 if max_z < 2.0 else 0  # int, bukan bool — agar JSON serializable

    for c in causes:
        c['combination_note'] = combination_anomaly

    return causes


# ─────────────────────────────────────────
# 4. ISOLATION FOREST
# ─────────────────────────────────────────
def run_isolation_forest(df_group, contamination):
    """PERBAIKAN: Validasi contamination 0-0.5"""
    feats = ['Rp', 'M3', 'Rp_per_M3']
    X = df_group[feats].values
    if len(X) < 4:
        return np.ones(len(X), dtype=int), np.zeros(len(X))
    
    # PERBAIKAN: Validasi contamination
    if contamination == 'auto':
        cont = 'auto'
    else:
        try:
            cont_val = float(contamination)
            cont = cont_val if 0 < cont_val <= 0.5 else 'auto'
        except:
            cont = 'auto'
    
    clf = IsolationForest(contamination=cont, random_state=42, n_estimators=100)
    clf.fit(X)
    labels = clf.predict(X)       # 1=normal, -1=anomaly
    scores = clf.score_samples(X) # negatif; makin negatif = makin anomalous
    return labels, scores


# ─────────────────────────────────────────
# 5. ANALISIS UTAMA
# ─────────────────────────────────────────
def analyze(filepath, contamination='0.05', mode='near_tahun_per_golongan',
            tahun_range=None):
    df = load_data(filepath)

    if tahun_range:
        t_min, t_max = int(tahun_range[0]), int(tahun_range[1])
        df = df[(df['Tahun'] >= t_min) & (df['Tahun'] <= t_max)]

    all_golongan = sorted(df['Golongan'].unique())
    df['is_anomaly']    = 0
    df['anomaly_score'] = 0.0
    df['anomaly_level'] = 'normal'
    df['causes']        = None

    group_stats = {}

    # ── Mode 1: Near Tahun per Golongan (default) ──
    if mode == 'near_tahun_per_golongan':
        for gol in all_golongan:
            for tahun in sorted(df['Tahun'].unique()):
                sub = df[(df['Golongan'] == gol) & (df['Tahun'] == tahun)]
                if len(sub) < 2: continue
                key = (gol, tahun)
                group_stats[key] = {}
                for attr in ['Rp', 'M3', 'Rp_per_M3']:
                    group_stats[key][attr] = {'mean': sub[attr].mean(), 'std': sub[attr].std()}
                labels, scores = run_isolation_forest(sub, contamination)
                is_anom = (labels == -1).astype(int)
                severity = assign_severity(scores, is_anom)
                df.loc[sub.index, 'is_anomaly']    = is_anom
                df.loc[sub.index, 'anomaly_score'] = scores
                df.loc[sub.index, 'anomaly_level'] = severity

    # ── Mode 2: Near Tahun Near Golongan ──
    # Setiap baris dibandingkan dalam konteks: tahun SAMA, semua golongan digabung
    # "Near Tahun" = filter per tahun (bukan lintas tahun)
    # "Near Golongan" = semua golongan dalam tahun itu dibandingkan bersama
    elif mode == 'near_tahun_near_golongan':
        for tahun in sorted(df['Tahun'].unique()):
            sub = df[df['Tahun'] == tahun]
            if len(sub) < 4: continue
            key = (tahun,)
            group_stats[key] = {}
            for attr in ['Rp', 'M3', 'Rp_per_M3']:
                group_stats[key][attr] = {'mean': sub[attr].mean(), 'std': sub[attr].std()}
            labels, scores = run_isolation_forest(sub, contamination)
            is_anom  = (labels == -1).astype(int)
            severity = assign_severity(scores, is_anom)
            df.loc[sub.index, 'is_anomaly']    = is_anom
            df.loc[sub.index, 'anomaly_score'] = scores
            df.loc[sub.index, 'anomaly_level'] = severity

    # ── Mode 3 & 4: Multi Tahun ──
    elif mode in ('multi_tahun_semua_golongan', 'multi_tahun_per_golongan'):
        if mode == 'multi_tahun_per_golongan':
            # Semua tahun digabung, tapi tetap dipisah per golongan
            for gol in all_golongan:
                sub = df[df['Golongan'] == gol]
                if len(sub) < 4: continue
                key = (gol,)
                group_stats[key] = {}
                for attr in ['Rp', 'M3', 'Rp_per_M3']:
                    group_stats[key][attr] = {'mean': sub[attr].mean(), 'std': sub[attr].std()}
                labels, scores = run_isolation_forest(sub, contamination)
                is_anom  = (labels == -1).astype(int)
                severity = assign_severity(scores, is_anom)
                df.loc[sub.index, 'is_anomaly']    = is_anom
                df.loc[sub.index, 'anomaly_score'] = scores
                df.loc[sub.index, 'anomaly_level'] = severity
        else:
            # Semua tahun + semua golongan digabung sekaligus
            key = ('ALL',)
            group_stats[key] = {}
            for attr in ['Rp', 'M3', 'Rp_per_M3']:
                group_stats[key][attr] = {'mean': df[attr].mean(), 'std': df[attr].std()}
            labels, scores = run_isolation_forest(df, contamination)
            is_anom  = (labels == -1).astype(int)
            severity = assign_severity(scores, is_anom)
            df['is_anomaly']    = is_anom
            df['anomaly_score'] = scores
            df['anomaly_level'] = severity

    # ── Hitung Z-Score causes untuk semua anomali ──
    for idx, row in df[df['is_anomaly'] == 1].iterrows():
        df.at[idx, 'causes'] = compute_zscore_causes(row, group_stats)

    return df, all_golongan


def analyze_json(json_file_path='stdin', contamination='0.05', mode='near_tahun_per_golongan',
                 tahun_range=None):
    """Analisis mode database: baca data dari JSON file atau stdin"""
    df = load_data_json(json_file_path)

    if tahun_range:
        t_min, t_max = int(tahun_range[0]), int(tahun_range[1])
        df = df[(df['Tahun'] >= t_min) & (df['Tahun'] <= t_max)]

    all_golongan = sorted(df['Golongan'].unique())
    df['is_anomaly']    = 0
    df['anomaly_score'] = 0.0
    df['anomaly_level'] = 'normal'
    df['causes']        = None

    group_stats = {}

    # ── Mode 1: Near Tahun per Golongan (default) ──
    if mode == 'near_tahun_per_golongan':
        for gol in all_golongan:
            for tahun in sorted(df['Tahun'].unique()):
                sub = df[(df['Golongan'] == gol) & (df['Tahun'] == tahun)]
                if len(sub) < 2: continue
                key = (gol, tahun)
                group_stats[key] = {}
                for attr in ['Rp', 'M3', 'Rp_per_M3']:
                    group_stats[key][attr] = {'mean': sub[attr].mean(), 'std': sub[attr].std()}
                labels, scores = run_isolation_forest(sub, contamination)
                is_anom = (labels == -1).astype(int)
                severity = assign_severity(scores, is_anom)
                df.loc[sub.index, 'is_anomaly']    = is_anom
                df.loc[sub.index, 'anomaly_score'] = scores
                df.loc[sub.index, 'anomaly_level'] = severity

    # ── Mode 2: Near Tahun Near Golongan ──
    # Filter per tahun, semua golongan dalam tahun itu dibandingkan bersama
    elif mode == 'near_tahun_near_golongan':
        for tahun in sorted(df['Tahun'].unique()):
            sub = df[df['Tahun'] == tahun]
            if len(sub) < 4: continue
            key = (tahun,)
            group_stats[key] = {}
            for attr in ['Rp', 'M3', 'Rp_per_M3']:
                group_stats[key][attr] = {'mean': sub[attr].mean(), 'std': sub[attr].std()}
            labels, scores = run_isolation_forest(sub, contamination)
            is_anom  = (labels == -1).astype(int)
            severity = assign_severity(scores, is_anom)
            df.loc[sub.index, 'is_anomaly']    = is_anom
            df.loc[sub.index, 'anomaly_score'] = scores
            df.loc[sub.index, 'anomaly_level'] = severity

    # ── Mode 3 & 4: Multi Tahun ──
    elif mode in ('multi_tahun_semua_golongan', 'multi_tahun_per_golongan'):
        if mode == 'multi_tahun_per_golongan':
            # Semua tahun digabung, dipisah per golongan
            for gol in all_golongan:
                sub = df[df['Golongan'] == gol]
                if len(sub) < 4: continue
                key = (gol,)
                group_stats[key] = {}
                for attr in ['Rp', 'M3', 'Rp_per_M3']:
                    group_stats[key][attr] = {'mean': sub[attr].mean(), 'std': sub[attr].std()}
                labels, scores = run_isolation_forest(sub, contamination)
                is_anom  = (labels == -1).astype(int)
                severity = assign_severity(scores, is_anom)
                df.loc[sub.index, 'is_anomaly']    = is_anom
                df.loc[sub.index, 'anomaly_score'] = scores
                df.loc[sub.index, 'anomaly_level'] = severity
        else:
            # Semua tahun + semua golongan digabung sekaligus
            key = ('ALL',)
            group_stats[key] = {}
            for attr in ['Rp', 'M3', 'Rp_per_M3']:
                group_stats[key][attr] = {'mean': df[attr].mean(), 'std': df[attr].std()}
            labels, scores = run_isolation_forest(df, contamination)
            is_anom  = (labels == -1).astype(int)
            severity = assign_severity(scores, is_anom)
            df['is_anomaly']    = is_anom
            df['anomaly_score'] = scores
            df['anomaly_level'] = severity

    # ── Hitung Z-Score causes untuk semua anomali ──
    for idx, row in df[df['is_anomaly'] == 1].iterrows():
        df.at[idx, 'causes'] = compute_zscore_causes(row, group_stats)

    return df, all_golongan


# ─────────────────────────────────────────
# 6. BUILD JSON
# ─────────────────────────────────────────
def build_json(df, all_golongan, contamination, mode):
    total   = len(df)
    anomali = int(df['is_anomaly'].sum())
    normal  = total - anomali
    pct     = round(anomali / total * 100, 2) if total > 0 else 0

    # Severity counts
    sev_counts = {
        'high':   int((df['anomaly_level'] == 'high').sum()),
        'medium': int((df['anomaly_level'] == 'medium').sum()),
        'low':    int((df['anomaly_level'] == 'low').sum()),
    }

    records = []
    for _, row in df.iterrows():
        causes = row.get('causes')
        if causes is None or not isinstance(causes, list):
            causes = []
        records.append({
            'tahun'        : int(row['Tahun']),
            'bulan'        : str(row['Bulan']),
            'bulan_num'    : int(row['BulanNum']),
            'golongan'     : str(row['Golongan']),
            'nama_golongan': str(row.get('NamaGolongan', row['Golongan'])),
            'rp'           : float(row['Rp']),
            'm3'           : float(row['M3']),
            'rp_per_m3'    : float(row['Rp_per_M3']),
            'is_anomaly'   : int(row['is_anomaly']),
            'anomaly_score': round(float(row['anomaly_score']), 6),
            'anomaly_level': str(row['anomaly_level']),
            'causes'       : causes,
        })

    # Summary per golongan
    namamap = {}
    for _, r in df.iterrows():
        namamap[r['Golongan']] = r.get('NamaGolongan', r['Golongan'])

    gol_summary = []
    for gol in all_golongan:
        sub  = df[df['Golongan'] == gol]
        anom = int(sub['is_anomaly'].sum())
        tot  = len(sub)
        gol_summary.append({
            'golongan': gol,
            'nama'    : namamap.get(gol, gol),
            'total'   : tot,
            'anomali' : anom,
            'normal'  : tot - anom,
            'pct'     : round(anom / tot * 100, 2) if tot > 0 else 0,
            'high'    : int((sub['anomaly_level'] == 'high').sum()),
            'medium'  : int((sub['anomaly_level'] == 'medium').sum()),
            'low'     : int((sub['anomaly_level'] == 'low').sum()),
        })

    # Summary per tahun
    tahun_summary = []
    for tahun in sorted(df['Tahun'].unique()):
        sub  = df[df['Tahun'] == tahun]
        anom = int(sub['is_anomaly'].sum())
        tot  = len(sub)
        tahun_summary.append({
            'tahun'  : int(tahun),
            'total'  : tot,
            'anomali': anom,
            'normal' : tot - anom,
            'pct'    : round(anom / tot * 100, 2) if tot > 0 else 0,
            'high'   : int((sub['anomaly_level'] == 'high').sum()),
            'medium' : int((sub['anomaly_level'] == 'medium').sum()),
            'low'    : int((sub['anomaly_level'] == 'low').sum()),
        })

    # Insights otomatis
    max_gol   = max(gol_summary, key=lambda x: x['anomali'], default=None)
    max_tahun = max(tahun_summary, key=lambda x: x['anomali'], default=None)
    insights  = []
    if max_gol and max_gol['anomali'] > 0:
        insights.append(
            f"Golongan {max_gol['golongan']} memiliki anomali terbanyak "
            f"({max_gol['anomali']} data, {max_gol['pct']}%)"
        )
    if max_tahun and max_tahun['anomali'] > 0:
        insights.append(
            f"Tahun {max_tahun['tahun']} mencatat anomali tertinggi "
            f"({max_tahun['anomali']} kasus)"
        )
    if sev_counts['high'] > 0:
        insights.append(
            f"{sev_counts['high']} anomali berkategori High (sangat menyimpang dari pola normal)"
        )
    if pct > 20:
        insights.append(f"Persentase anomali {pct}% tergolong tinggi, perlu investigasi mendalam")
    elif pct < 5:
        insights.append(f"Persentase anomali {pct}% tergolong rendah, data relatif konsisten")

    return {
        'status': 'success',
        'meta': {
            'total'         : total,
            'anomali'       : anomali,
            'normal'        : normal,
            'pct_anomali'   : pct,
            'contamination' : str(contamination),
            'mode'          : mode,
            'tahun_range'   : [int(df['Tahun'].min()), int(df['Tahun'].max())],
            'golongan_list' : all_golongan,
            'severity'      : sev_counts,
            'insights'      : insights,
        },
        'summary_golongan': gol_summary,
        'summary_tahun'   : tahun_summary,
        'data'            : records,
    }


# ─────────────────────────────────────────
# 7. MAIN
# ─────────────────────────────────────────
if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('--file',          required=True, help='Excel file path')
    parser.add_argument('--contamination', default='0.05')
    parser.add_argument('--mode',          default='near_tahun_per_golongan')
    parser.add_argument('--tahun_min',     type=int, default=None)
    parser.add_argument('--tahun_max',     type=int, default=None)
    parser.add_argument('--output',        default=None)  # None = stdout mode
    parser.add_argument('--stdout',        action='store_true', help='Output full result to stdout')
    parser.add_argument('--json-input',    action='store_true', help='Read input as JSON format from stdin (database mode)')
    args = parser.parse_args()

    tahun_range = None
    if args.tahun_min and args.tahun_max:
        tahun_range = (args.tahun_min, args.tahun_max)

    cont = args.contamination

    try:
        # Mode JSON Input (database): baca dari file JSON (via --file)
        if args.json_input:
            df, all_golongan = analyze_json(args.file, cont, args.mode, tahun_range)
        else:
            df, all_golongan = analyze(args.file, cont, args.mode, tahun_range)
        result = build_json(df, all_golongan, cont, args.mode)
        
        # MODE STDOUT (User mode): Print full result JSON
        if args.stdout or args.output is None:
            # Print complete result to stdout for PHP to capture
            print(json.dumps(result, ensure_ascii=False, cls=NumpyEncoder))
        else:
            # MODE FILE (Guest mode): Save to file + print summary
            os.makedirs(os.path.dirname(os.path.abspath(args.output)), exist_ok=True)
            with open(args.output, 'w', encoding='utf-8') as f:
                json.dump(result, f, ensure_ascii=False, indent=2, cls=NumpyEncoder)
            
            # Print summary untuk PHP
            print(json.dumps({
                'status' : 'success',
                'output' : args.output,
                'total'  : result['meta']['total'],
                'anomali': result['meta']['anomali'],
                'severity': result['meta']['severity'],
            }, cls=NumpyEncoder))
    except Exception as e:
        error_result = {
            'status': 'error',
            'message': str(e),
            'traceback': str(sys.exc_info())
        }
        print(json.dumps(error_result, ensure_ascii=False, cls=NumpyEncoder))
        sys.exit(1)
