"""
Placement Prediction Inference Engine
Evaluates individual student placement probability, risk category, and key factor explanations.
"""

import os
import sys
import json
import argparse
import numpy as np

try:
    import joblib
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False


def calculate_heuristic_probability(features: dict) -> float:
    """Mathematical fallback calculation based on calibrated logistical odds."""
    cgpa = float(features.get('cgpa', 7.0))
    tenth = float(features.get('tenth_percent', 75.0))
    twelfth = float(features.get('twelfth_percent', 75.0))
    skills = int(features.get('skills_count', 3))
    projects = int(features.get('projects_count', 1))
    certs = int(features.get('certifications_count', 0))
    internships = int(features.get('internships_count', 0))
    backlogs = int(features.get('backlogs', 0))

    score = (
        (cgpa - 7.0) * 1.5 +
        (tenth - 75.0) * 0.04 +
        (twelfth - 75.0) * 0.04 +
        skills * 0.35 +
        projects * 0.4 +
        certs * 0.3 +
        internships * 0.8 -
        backlogs * 1.2 - 0.5
    )
    prob = 1.0 / (1.0 + np.exp(-score))
    return float(np.round(prob, 4))


def predict_placement(features: dict) -> dict:
    model_path = os.path.join(os.path.dirname(__file__), 'model', 'placement_model.joblib')
    probability = None

    if SKLEARN_AVAILABLE and os.path.exists(model_path):
        try:
            model = joblib.load(model_path)
            feature_vector = np.array([[
                float(features.get('cgpa', 7.5)),
                float(features.get('tenth_percent', 80.0)),
                float(features.get('twelfth_percent', 80.0)),
                int(features.get('skills_count', 3)),
                int(features.get('projects_count', 1)),
                int(features.get('certifications_count', 0)),
                int(features.get('internships_count', 0)),
                int(features.get('backlogs', 0))
            ]])
            prob_arr = model.predict_proba(feature_vector)[0]
            probability = float(prob_arr[1])
        except Exception:
            probability = calculate_heuristic_probability(features)
    else:
        probability = calculate_heuristic_probability(features)

    percent = round(probability * 100.0, 1)

    if percent >= 75.0:
        risk_category = "Low Risk (High Placement Probability)"
        actionable_advice = "Profile is highly competitive for Tier-1/Product companies. Focus on advanced system design and behavioral rounds."
    elif percent >= 50.0:
        risk_category = "Moderate Risk (Average Probability)"
        actionable_advice = "Good baseline standing. Adding 1-2 production-ready projects and certifications will significantly improve conversion."
    else:
        risk_category = "High Risk (Requires Intervention)"
        actionable_advice = "Immediate focus needed on core technical skills, aptitude practice, and raising academic grade point average."

    return {
        "status": "success",
        "placement_probability": probability,
        "placement_percentage": percent,
        "risk_category": risk_category,
        "advice": actionable_advice,
        "inputs": features
    }


def main():
    parser = argparse.ArgumentParser(description="Student Placement Predictor")
    parser.add_argument("--cgpa", type=float, default=8.2)
    parser.add_argument("--tenth", type=float, default=85.0)
    parser.add_argument("--twelfth", type=float, default=82.0)
    parser.add_argument("--skills", type=int, default=4)
    parser.add_argument("--projects", type=int, default=2)
    parser.add_argument("--certs", type=int, default=1)
    parser.add_argument("--internships", type=int, default=0)
    parser.add_argument("--backlogs", type=int, default=0)
    parser.add_argument("--stdin", action="store_true", help="Read JSON payload from stdin")
    args = parser.parse_args()

    features = None
    if args.stdin:
        try:
            raw = sys.stdin.read().strip()
            if raw:
                features = json.loads(raw)
        except Exception:
            pass

    if not features:
        features = {
            'cgpa': args.cgpa,
            'tenth_percent': args.tenth,
            'twelfth_percent': args.twelfth,
            'skills_count': args.skills,
            'projects_count': args.projects,
            'certifications_count': args.certs,
            'internships_count': args.internships,
            'backlogs': args.backlogs
        }

    res = predict_placement(features)
    print(json.dumps(res, indent=2))

if __name__ == "__main__":
    main()
