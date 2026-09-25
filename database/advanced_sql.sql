-- ====================================================================
-- ADVANCED SQL SCRIPTS (15 MARKS DEMONSTRATION)
-- Subqueries, Correlated Subqueries, CTEs, Window Functions, and Analytics
-- ====================================================================

USE placement_management;

-- ====================================================================
-- SECTION 1: SUBQUERIES & CORRELATED SUBQUERIES
-- ====================================================================

-- 1.1 Multi-row Subquery: Students who have applied to Top-Tier companies offering > 15 LPA
SELECT 
    s.student_id,
    s.roll_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.cgpa,
    b.branch_code
FROM students s
JOIN branches b ON s.branch_id = b.branch_id
WHERE s.student_id IN (
    SELECT DISTINCT a.student_id
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE j.salary_stipend >= 1500000
);

-- 1.2 Correlated Subquery: Students whose CGPA is higher than their department average
SELECT 
    s.student_id,
    s.roll_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    d.dept_name,
    s.cgpa,
    ROUND((
        SELECT AVG(s2.cgpa)
        FROM students s2
        JOIN branches b2 ON s2.branch_id = b2.branch_id
        WHERE b2.dept_id = d.dept_id
    ), 2) AS department_avg_cgpa
FROM students s
JOIN branches b ON s.branch_id = b.branch_id
JOIN departments d ON b.dept_id = d.dept_id
WHERE s.cgpa > (
    SELECT AVG(s2.cgpa)
    FROM students s2
    JOIN branches b2 ON s2.branch_id = b2.branch_id
    WHERE b2.dept_id = d.dept_id
)
ORDER BY d.dept_name, s.cgpa DESC;

-- 1.3 Correlated Subquery: Jobs that have received above-average number of applications
SELECT 
    j.job_id,
    j.title,
    c.company_name,
    (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS total_applications
FROM jobs j
JOIN companies c ON j.company_id = c.company_id
WHERE (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) > (
    SELECT AVG(app_count) FROM (
        SELECT COUNT(*) AS app_count FROM applications GROUP BY job_id
    ) AS job_app_stats
)
ORDER BY total_applications DESC;

-- 1.4 NOT EXISTS: Students who possess all required skills for a specific job (Job ID = 1)
SELECT 
    s.student_id,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.cgpa
FROM students s
WHERE NOT EXISTS (
    SELECT js.skill_id 
    FROM job_skills js 
    WHERE js.job_id = 1 AND js.is_required = 1
    AND js.skill_id NOT IN (
        SELECT ss.skill_id 
        FROM student_skills ss 
        WHERE ss.student_id = s.student_id
    )
);


-- ====================================================================
-- SECTION 2: COMMON TABLE EXPRESSIONS (CTEs)
-- ====================================================================

-- 2.1 Multi-stage CTE: Recruitment Conversion Funnel Analytics
WITH JobApplicationMetrics AS (
    SELECT 
        j.job_id,
        j.title,
        c.company_name,
        COUNT(a.application_id) AS applied_count,
        COUNT(CASE WHEN a.status IN ('SHORTLISTED','INTERVIEW_SCHEDULED','SELECTED') THEN 1 END) AS shortlisted_count,
        COUNT(CASE WHEN a.status = 'SELECTED' THEN 1 END) AS selected_count
    FROM jobs j
    JOIN companies c ON j.company_id = c.company_id
    LEFT JOIN applications a ON j.job_id = a.job_id
    GROUP BY j.job_id, j.title, c.company_name
),
ConversionRatios AS (
    SELECT 
        job_id,
        title,
        company_name,
        applied_count,
        shortlisted_count,
        selected_count,
        ROUND((shortlisted_count * 100.0) / NULLIF(applied_count, 0), 2) AS shortlist_rate_pct,
        ROUND((selected_count * 100.0) / NULLIF(applied_count, 0), 2) AS selection_rate_pct
    FROM JobApplicationMetrics
)
SELECT * FROM ConversionRatios
WHERE applied_count > 0
ORDER BY selection_rate_pct DESC, applied_count DESC;

-- 2.2 CTE: Top Demanded Skills with Student Availability and Gap Analysis
WITH SkillDemand AS (
    SELECT 
        sk.skill_id,
        sk.skill_name,
        sk.category,
        COUNT(DISTINCT js.job_id) AS demand_job_count
    FROM skills sk
    LEFT JOIN job_skills js ON sk.skill_id = js.skill_id
    GROUP BY sk.skill_id, sk.skill_name, sk.category
),
SkillSupply AS (
    SELECT 
        ss.skill_id,
        COUNT(DISTINCT ss.student_id) AS supply_student_count
    FROM student_skills ss
    GROUP BY ss.skill_id
)
SELECT 
    d.skill_name,
    d.category,
    d.demand_job_count AS industry_demand,
    COALESCE(s.supply_student_count, 0) AS campus_talent_pool,
    (d.demand_job_count - COALESCE(s.supply_student_count, 0)) AS skill_deficit
FROM SkillDemand d
LEFT JOIN SkillSupply s ON d.skill_id = s.skill_id
ORDER BY industry_demand DESC;


-- ====================================================================
-- SECTION 3: WINDOW FUNCTIONS (Ranking, Partitioning & Running Totals)
-- ====================================================================

-- 3.1 DENSE_RANK() & NTILE(): Student Academic Quartiles and Branch Rank
SELECT 
    s.student_id,
    s.roll_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    b.branch_code,
    s.cgpa,
    DENSE_RANK() OVER (
        PARTITION BY s.branch_id 
        ORDER BY s.cgpa DESC
    ) AS branch_academic_rank,
    RANK() OVER (
        ORDER BY s.cgpa DESC
    ) AS college_wide_rank,
    NTILE(4) OVER (
        ORDER BY s.cgpa DESC
    ) AS cgpa_quartile
FROM students s
JOIN branches b ON s.branch_id = b.branch_id
ORDER BY b.branch_code, branch_academic_rank;

-- 3.2 ROW_NUMBER() & PARTITION BY: Highest Compensation Offer per Company
SELECT * FROM (
    SELECT 
        p.placement_id,
        c.company_name,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.roll_number,
        j.title AS job_role,
        p.package_lpa,
        ROW_NUMBER() OVER (
            PARTITION BY p.company_id 
            ORDER BY p.package_lpa DESC, p.accepted_at ASC
        ) AS offer_rank_in_company
    FROM placements p
    JOIN companies c ON p.company_id = c.company_id
    JOIN students s ON p.student_id = s.student_id
    JOIN jobs j ON p.job_id = j.job_id
) AS ranked_offers
WHERE offer_rank_in_company = 1;

-- 3.3 Analytic Windows: Running Total of Placements and Department Averages
SELECT 
    p.placement_id,
    p.accepted_at,
    d.dept_name,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    p.package_lpa,
    ROUND(AVG(p.package_lpa) OVER (PARTITION BY d.dept_id), 2) AS dept_avg_package,
    COUNT(p.placement_id) OVER (
        ORDER BY p.accepted_at 
        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
    ) AS cumulative_campus_placements
FROM placements p
JOIN students s ON p.student_id = s.student_id
JOIN branches b ON s.branch_id = b.branch_id
JOIN departments d ON b.dept_id = d.dept_id
ORDER BY p.accepted_at ASC;
