import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
import warnings

class nunpyEncoder(Json.JSONEncoder):
    ""Handline""
    def default(self,obj):
        if isinstance(obj, (np.integer,)):
        if isinstance(obj, (np.floating,)):
        if isinstance(obj, (np.bool_,)):
        if isinstance(obj, np.ndarray):
        if isinstance(obj, bool):
        return super().default(obj)
    
warnings.filterwarnings('ignore')

BULAN_ORDER = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JULI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER']
BULAN_NUM = [b: i+1 for i,b in enumerate(BULAN_ORDER)]

ALTER_LABELS = {'RP':'RP',}