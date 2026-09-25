"""
AI/ML Data Preprocessing Pipeline
Handles text normalization, TF-IDF vectorization, feature scaling and encoding.
"""

import re
import numpy as np

def clean_text(text: str) -> str:
    """Normalize text by removing punctuation, converting to lower case and stripping whitespace."""
    if not text or not isinstance(text, str):
        return ""
    text = text.lower()
    text = re.sub(r'[^a-zA-Z0-9\s+#.]', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text

def build_student_corpus(skills: list, projects: list, branch_name: str) -> str:
    """Combines student attributes into a comprehensive textual corpus for TF-IDF feature extraction."""
    skill_tokens = " ".join([clean_text(s) for s in skills])
    project_tokens = " ".join([clean_text(p.get('technologies', '') + " " + p.get('title', '') + " " + p.get('description', '')) for p in projects])
    branch_tokens = clean_text(branch_name)
    # Give higher weighting to explicit skills by repeating tokens
    return f"{skill_tokens} {skill_tokens} {project_tokens} {branch_tokens}"

def build_job_corpus(title: str, description: str, skills: list) -> str:
    """Combines job requirements into a target corpus."""
    title_tokens = clean_text(title)
    desc_tokens = clean_text(description)
    skills_tokens = " ".join([clean_text(s) for s in skills])
    return f"{title_tokens} {skills_tokens} {skills_tokens} {desc_tokens}"

def min_max_scale(val: float, min_val: float, max_val: float) -> float:
    """Scale value into [0, 1] range."""
    if max_val <= min_val:
        return 0.0
    return max(0.0, min(1.0, (val - min_val) / (max_val - min_val)))
