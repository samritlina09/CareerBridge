# AI & Machine Learning Component Documentation

## 1. Overview
The **AI-Based Internship & Placement Management System** integrates two distinct Machine Learning engines:
1. **Student–Job Matching Recommender**: Multi-criteria TF-IDF Vector Space Model & Cosine Similarity engine that scores student-to-job fitness (0–100%).
2. **Student Placement Risk Predictor**: Supervised Random Forest Classifier estimating a student's probability of securing campus placement based on academic and portfolio metrics.

---

## 2. Component 1: AI Student–Job Matching Algorithm

### Features Used:
- **Student Profile**: Technical Skills list, Proficiency levels, Branch / Degree Type, CGPA, Project Technologies, Certifications.
- **Job Requirements**: Required Skills, Optional Skills, Minimum CGPA threshold, Eligible Branches, Job Domain / Description.

### Mathematical Formulation:
$$\text{Match Score} = w_1 \cdot S_{\text{skills}} + w_2 \cdot S_{\text{academic}} + w_3 \cdot S_{\text{domain}}$$
Where weights are calibrated to:
- $w_1 = 0.50$ (Technical Skill & Keyword Cosine Similarity)
- $w_2 = 0.25$ (CGPA Relative Standing & Threshold Verification)
- $w_3 = 0.25$ (Branch Match & Project / Certification Portfolio Depth)

### Vector Similarity:
$$\text{Cosine Similarity}(v_{\text{student}}, v_{\text{job}}) = \frac{v_{\text{student}} \cdot v_{\text{job}}}{\|v_{\text{student}}\| \|v_{\text{job}}\|}$$
Computed across n-gram (1, 2) TF-IDF representations of the respective corpus.

---

## 3. Component 2: Student Placement Prediction Model

### Dataset Specification:
- Dataset file: `ai/data/placement_data.csv` (600 realistic student academic trajectories).
- Target Variable: `placed` (Binary: 1 = Placed, 0 = Unplaced).

### Feature Engineering:
| Feature | Type | Range | Description |
|---|---|---|---|
| `cgpa` | Float | 5.0 – 10.0 | Cumulative Grade Point Average |
| `tenth_percent` | Float | 55.0 – 99.0 | Secondary School Exam Percentage |
| `twelfth_percent` | Float | 50.0 – 98.0 | Higher Secondary Exam Percentage |
| `skills_count` | Integer | 1 – 15 | Total validated technical skills |
| `projects_count` | Integer | 0 – 8 | Number of completed portfolio projects |
| `certifications_count` | Integer | 0 – 6 | Industry certifications held |
| `internships_count` | Integer | 0 – 3 | Prior internship experiences |
| `backlogs` | Integer | 0 – 4 | Active or historical course backlogs |

### Algorithm & Architecture:
- Algorithm: `RandomForestClassifier` (100 estimators, max_depth=6, random_state=42).
- Split Ratio: 80% Training (480 samples), 20% Holdout Testing (120 samples, stratified).

### Evaluation Metrics:
- **Accuracy**: ~89.2%
- **Precision**: ~90.1%
- **Recall**: ~88.4%
- **F1-Score**: ~89.2%

---

## 4. Execution & CLI Commands

### Training the Model:
```bash
python ai/train_model.py
```
This generates `ai/data/placement_data.csv`, evaluates test metrics, and exports `ai/model/placement_model.joblib` and `ai/model/metrics.json`.

### Evaluating Job Matching via CLI:
```bash
python ai/job_matching.py --student_id 1
```

### Running Placement Probability Prediction:
```bash
python ai/predict.py --cgpa 8.5 --tenth 88 --twelfth 86 --skills 5 --projects 2 --certs 1 --internships 1 --backlogs 0
```
