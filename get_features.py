import joblib
import json

try:
    model = joblib.load('ml_backend/model/xgboost_model.joblib')
    features = model.get_booster().feature_names
    with open('features_list.json', 'w', encoding='utf-8') as f:
        json.dump(features, f, indent=4)
    print("Features extracted successfully.")
except Exception as e:
    print(f"Error: {e}")
