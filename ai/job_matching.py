"""
AI-Based Student–Job Matching Algorithm
Multi-Criteria Recommendation Engine combining TF-IDF Cosine Similarity,
Academic Threshold Verification, and Domain Relevance Scoring.
"""

import sys
import json
import argparse
import math
from collections import Counter
from preprocessing import clean_text, build_student_corpus, build_job_corpus

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False


def pure_cosine_similarity(vec1: Counter, vec2: Counter) -> float:
    """Fallback cosine similarity between two term-frequency counter vectors."""
    intersection = set(vec1.keys()) & set(vec2.keys())
    numerator = sum([vec1[x] * vec2[x] for x in intersection])
    sum1 = sum([val**2 for val in vec1.values()])
    sum2 = sum([val**2 for val in vec2.values()])
    denominator = math.sqrt(sum1) * math.sqrt(sum2)
    if not denominator:
        return 0.0
    return float(numerator) / denominator


def calculate_match_score(student: dict, job: dict) -> dict:
    """
    Computes a hybrid match score between student profile and job posting.
    Formula:
      Match Score = (0.50 * Skill_Similarity) + (0.25 * CGPA_Score) + (0.25 * Domain_Eligibility_Score)
    """
    # 1. Skill Corpus & Vector Similarity (50% weight)
    student_skills = [s if isinstance(s, str) else s.get('skill_name', '') for s in student.get('skills', [])]
    job_skills = [s if isinstance(s, str) else s.get('skill_name', '') for s in job.get('skills', [])]
    
    student_corpus = build_student_corpus(student_skills, student.get('projects', []), student.get('branch_name', ''))
    job_corpus = build_job_corpus(job.get('title', ''), job.get('description', ''), job_skills)

    text_similarity = 0.0
    if SKLEARN_AVAILABLE:
        vectorizer = TfidfVectorizer(ngram_range=(1, 2), stop_words='english')
        try:
            tfidf_matrix = vectorizer.fit_transform([student_corpus, job_corpus])
            text_similarity = float(cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:2])[0][0])
        except Exception:
            text_similarity = 0.5
    else:
        v1 = Counter(clean_text(student_corpus).split())
        v2 = Counter(clean_text(job_corpus).split())
        text_similarity = pure_cosine_similarity(v1, v2)

    # Calculate exact skill overlap
    clean_student_skills = set([clean_text(s) for s in student_skills])
    matched_skills = []
    missing_skills = []

    for js in job_skills:
        cjs = clean_text(js)
        if any(cjs in css or css in cjs for css in clean_student_skills):
            matched_skills.append(js)
        else:
            missing_skills.append(js)

    overlap_ratio = len(matched_skills) / max(1, len(job_skills)) if job_skills else 1.0
    combined_skill_score = (0.6 * text_similarity) + (0.4 * overlap_ratio)

    # 2. CGPA Score (25% weight)
    student_cgpa = float(student.get('cgpa', 0.0))
    min_cgpa = float(job.get('min_cgpa', 0.0))
    if min_cgpa > 0:
        if student_cgpa >= min_cgpa:
            # Reward higher CGPA above minimum
            cgpa_score = 0.8 + min(0.2, (student_cgpa - min_cgpa) / 5.0)
        else:
            # Penalize shortfall
            cgpa_score = max(0.0, (student_cgpa / min_cgpa) * 0.6)
    else:
        cgpa_score = 1.0

    # 3. Branch & Experience Eligibility (25% weight)
    eligible_branches = [b if isinstance(b, str) else b.get('branch_code', '') for b in job.get('branches', [])]
    student_branch = student.get('branch_code', '')
    
    if not eligible_branches or (student_branch and student_branch in eligible_branches):
        branch_score = 1.0
    else:
        branch_score = 0.3 # Partial eligibility

    # Project relevance bonus
    project_bonus = min(0.1, len(student.get('projects', [])) * 0.03)
    cert_bonus = min(0.1, len(student.get('certifications', [])) * 0.03)
    domain_score = min(1.0, branch_score + project_bonus + cert_bonus)

    # Composite Final Score (0 to 100)
    final_score = (combined_skill_score * 50.0) + (cgpa_score * 25.0) + (domain_score * 25.0)
    final_score = round(max(15.0, min(99.0, final_score)), 1)

    return {
        'job_id': job.get('job_id'),
        'job_title': job.get('title'),
        'company_name': job.get('company_name'),
        'job_type': job.get('job_type'),
        'location': job.get('location'),
        'salary_stipend': job.get('salary_stipend'),
        'match_score': final_score,
        'skill_score': round(combined_skill_score * 100, 1),
        'cgpa_score': round(cgpa_score * 100, 1),
        'matched_skills': matched_skills,
        'missing_skills': missing_skills,
        'is_cgpa_eligible': student_cgpa >= min_cgpa,
        'recommendation_level': 'High Match' if final_score >= 80 else ('Moderate Match' if final_score >= 60 else 'Low Match')
    }


def main():
    parser = argparse.ArgumentParser(description="AI Job Matcher")
    parser.add_argument("--student_id", type=int, help="Student ID to match")
    parser.add_argument("--stdin", action="store_true", help="Read JSON payload from stdin")
    args = parser.parse_args()

    input_data = None
    if args.stdin:
        try:
            raw = sys.stdin.read()
            if raw.strip():
                input_data = json.loads(raw)
        except Exception:
            pass

    if not input_data:
        # Standalone mock / demonstration data
        student = {
            "student_id": args.student_id or 1,
            "cgpa": 9.45,
            "branch_code": "BTECH-CSE",
            "branch_name": "Computer Science & Engineering",
            "skills": ["Python", "JavaScript", "React.js", "Node.js", "SQL", "Docker"],
            "projects": [{"title": "Cloud Native Microservices Portal", "technologies": "React, Node.js, Docker"}],
            "certifications": [{"title": "AWS Certified Solutions Architect"}]
        }
        jobs = [
            {
                "job_id": 1,
                "title": "Software Engineer - University Graduate",
                "company_name": "Google",
                "job_type": "FULL_TIME",
                "location": "Bangalore, Karnataka",
                "salary_stipend": 3200000.0,
                "min_cgpa": 8.5,
                "description": "Build large-scale distributed systems in C++, Java, and Python.",
                "skills": ["C++", "Data Structures & Algorithms", "SQL", "Python"],
                "branches": ["BTECH-CSE", "BTECH-AIML", "BTECH-IT"]
            },
            {
                "job_id": 2,
                "title": "Machine Learning Research Intern",
                "company_name": "Google",
                "job_type": "INTERNSHIP",
                "location": "Bangalore, Karnataka",
                "salary_stipend": 90000.0,
                "min_cgpa": 8.5,
                "description": "Deep learning and neural networks research with TensorFlow and Python.",
                "skills": ["Python", "Machine Learning", "TensorFlow"],
                "branches": ["BTECH-CSE", "BTECH-AIML"]
            },
            {
                "job_id": 15,
                "title": "Member of Technical Staff - Creative Cloud",
                "company_name": "Adobe Systems",
                "job_type": "FULL_TIME",
                "location": "Noida, Uttar Pradesh",
                "salary_stipend": 2600000.0,
                "min_cgpa": 8.5,
                "description": "Web rendering, canvas manipulation, React and JavaScript.",
                "skills": ["JavaScript", "React.js", "Data Structures & Algorithms"],
                "branches": ["BTECH-CSE", "BTECH-AIML", "BTECH-IT"]
            }
        ]
    else:
        student = input_data.get('student', {})
        jobs = input_data.get('jobs', [])

    results = []
    for job in jobs:
        results.append(calculate_match_score(student, job))

    results.sort(key=lambda x: x['match_score'], reverse=True)

    output = {
        "status": "success",
        "student_id": student.get("student_id"),
        "total_evaluated": len(jobs),
        "engine": "Scikit-Learn TF-IDF Cosine Similarity" if SKLEARN_AVAILABLE else "Custom Term-Frequency Cosine Algorithm",
        "recommendations": results
    }
    print(json.dumps(output, indent=2))

if __name__ == "__main__":
    main()
