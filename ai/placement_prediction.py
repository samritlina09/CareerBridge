"""
Placement Prediction Library Wrapper
Provides batch analysis for institutional reports and student profile integration.
"""

from predict import predict_placement

def batch_evaluate_students(student_records: list) -> list:
    """Evaluates placement probabilities for a cohort of students."""
    evaluated = []
    for record in student_records:
        res = predict_placement(record)
        record_res = dict(record)
        record_res['prediction'] = res
        evaluated.append(record_res)
    return evaluated

if __name__ == "__main__":
    sample_cohort = [
        {"student_id": 1, "name": "Aarav Sharma", "cgpa": 9.45, "skills_count": 7, "projects_count": 3, "certifications_count": 2, "internships_count": 1, "backlogs": 0},
        {"student_id": 5, "name": "Kunal Singh", "cgpa": 8.20, "skills_count": 4, "projects_count": 1, "certifications_count": 0, "internships_count": 0, "backlogs": 0},
        {"student_id": 17, "name": "Akash Mishra", "cgpa": 7.40, "skills_count": 3, "projects_count": 0, "certifications_count": 0, "internships_count": 0, "backlogs": 1}
    ]
    results = batch_evaluate_students(sample_cohort)
    for r in results:
        print(f"Student: {r['name']} | Probability: {r['prediction']['placement_percentage']}% | Risk: {r['prediction']['risk_category']}")
