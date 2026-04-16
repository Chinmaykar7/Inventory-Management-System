import joblib
import json

features = joblib.load('ml_backend/model/model_features.joblib')
with open('feature_test.json', 'w', encoding='utf-8') as f:
    json.dump(features, f, indent=4)
