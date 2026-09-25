-- ====================================================================
-- REALISTIC SAMPLE DATA FOR PLACEMENT MANAGEMENT SYSTEM
-- ====================================================================

USE placement_management;

-- Common password hash for 'Admin@123': $2y$10$H/z2YLeTrtWf7b4Ki/L0LuuIXnXP5Z0cq8UBT/UaYCSJnNBFyTzJO
SET @DEFAULT_PW = '$2y$10$H/z2YLeTrtWf7b4Ki/L0LuuIXnXP5Z0cq8UBT/UaYCSJnNBFyTzJO';

-- --------------------------------------------------------------------
-- 1. MASTER DEPARTMENTS
-- --------------------------------------------------------------------
INSERT INTO departments (dept_id, dept_name, dept_code) VALUES
(1, 'Computer Science & Engineering', 'CSE'),
(2, 'Information Technology', 'IT'),
(3, 'Electronics & Communication Engineering', 'ECE'),
(4, 'Mechanical Engineering', 'MECH'),
(5, 'Computer Applications', 'MCA');

-- --------------------------------------------------------------------
-- 2. MASTER BRANCHES
-- --------------------------------------------------------------------
INSERT INTO branches (branch_id, dept_id, branch_name, branch_code, degree_type) VALUES
(1, 1, 'Computer Science & Engineering', 'BTECH-CSE', 'B.Tech'),
(2, 1, 'Artificial Intelligence & Machine Learning', 'BTECH-AIML', 'B.Tech'),
(3, 1, 'Data Science', 'BTECH-DS', 'B.Tech'),
(4, 2, 'Information Technology', 'BTECH-IT', 'B.Tech'),
(5, 3, 'Electronics & Communication', 'BTECH-ECE', 'B.Tech'),
(6, 4, 'Mechanical Engineering', 'BTECH-ME', 'B.Tech'),
(7, 5, 'Master of Computer Applications', 'MCA-PG', 'MCA');

-- --------------------------------------------------------------------
-- 3. MASTER SKILLS
-- --------------------------------------------------------------------
INSERT INTO skills (skill_id, skill_name, category) VALUES
(1, 'Python', 'Programming Language'),
(2, 'Java', 'Programming Language'),
(3, 'C++', 'Programming Language'),
(4, 'JavaScript', 'Web Development'),
(5, 'React.js', 'Frontend Framework'),
(6, 'Node.js', 'Backend Framework'),
(7, 'SQL', 'Database'),
(8, 'Machine Learning', 'Artificial Intelligence'),
(9, 'Docker', 'DevOps & Cloud'),
(10, 'AWS', 'DevOps & Cloud'),
(11, 'Git & GitHub', 'Tools & Version Control'),
(12, 'Data Structures & Algorithms', 'Core CS'),
(13, 'Spring Boot', 'Backend Framework'),
(14, 'TensorFlow', 'Artificial Intelligence'),
(15, 'HTML5 & CSS3', 'Web Development'),
(16, 'Linux / Bash', 'Operating Systems'),
(17, 'PostgreSQL', 'Database'),
(18, 'Kubernetes', 'DevOps & Cloud'),
(19, 'Natural Language Processing', 'Artificial Intelligence'),
(20, 'Cybersecurity Fundamentals', 'Security');

-- --------------------------------------------------------------------
-- 4. COMPANIES
-- --------------------------------------------------------------------
INSERT INTO companies (company_id, company_name, website, industry, description, location, is_verified) VALUES
(1, 'Google', 'https://about.google', 'Information Technology', 'Multinational technology company specializing in online services and AI.', 'Bangalore, Karnataka', 1),
(2, 'Microsoft', 'https://microsoft.com', 'Software & Cloud', 'Global leader in software, computing services, and cloud solutions.', 'Hyderabad, Telangana', 1),
(3, 'Amazon', 'https://amazon.jobs', 'E-Commerce & Cloud', 'World leader in cloud computing (AWS) and e-commerce logistics.', 'Bangalore, Karnataka', 1),
(4, 'Infosys', 'https://infosys.com', 'IT Consulting', 'Global leader in next-generation digital services and consulting.', 'Pune, Maharashtra', 1),
(5, 'Tata Consultancy Services', 'https://tcs.com', 'IT Services', 'Largest Indian multinational information technology service and consulting firm.', 'Mumbai, Maharashtra', 1),
(6, 'Wipro', 'https://wipro.com', 'IT Consulting', 'Global information technology, consulting and business process services company.', 'Bangalore, Karnataka', 1),
(7, 'Accenture', 'https://accenture.com', 'Management & Tech Consulting', 'Global professional services company with leading capabilities in digital, cloud and security.', 'Gurgaon, Haryana', 1),
(8, 'Cisco Systems', 'https://cisco.com', 'Networking & Telecommunications', 'Worldwide leader in technology that powers the Internet and networking infrastructure.', 'Bangalore, Karnataka', 1),
(9, 'Qualcomm', 'https://qualcomm.com', 'Semiconductors & Telecommunications', 'World leader in 5G wireless technology and Snapdragon semiconductor chipsets.', 'Hyderabad, Telangana', 1),
(10, 'Adobe Systems', 'https://adobe.com', 'Creative Software & Cloud', 'Global leader in digital media and digital marketing software solutions.', 'Noida, Uttar Pradesh', 1),
(11, 'Deloitte', 'https://deloitte.com', 'Financial & Tech Advisory', 'Audit, consulting, financial advisory, and cyber risk services provider.', 'Hyderabad, Telangana', 1),
(12, 'IBM', 'https://ibm.com', 'Enterprise Tech & AI', 'Pioneer in enterprise infrastructure, hybrid cloud, and quantum computing.', 'Bangalore, Karnataka', 1);

-- --------------------------------------------------------------------
-- 5. USERS & DEFAULT ADMIN ACCOUNT
-- Email: admin@campusplacement.com, Password: Admin@123
-- --------------------------------------------------------------------
INSERT INTO users (user_id, email, password_hash, role, status) VALUES
(1, 'admin@campusplacement.com', @DEFAULT_PW, 'ADMIN', 'ACTIVE');

-- Recruiter Users (Users 2 to 13)
INSERT INTO users (user_id, email, password_hash, role, status) VALUES
(2, 'recruiter.google@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(3, 'recruiter.microsoft@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(4, 'recruiter.amazon@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(5, 'recruiter.infosys@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(6, 'recruiter.tcs@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(7, 'recruiter.wipro@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(8, 'recruiter.accenture@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(9, 'recruiter.cisco@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(10, 'recruiter.qualcomm@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(11, 'recruiter.adobe@campus.com', @DEFAULT_PW, 'RECRUITER', 'ACTIVE'),
(12, 'pending.recruiter1@startup.io', @DEFAULT_PW, 'RECRUITER', 'PENDING'),
(13, 'pending.recruiter2@fintech.co', @DEFAULT_PW, 'RECRUITER', 'PENDING');

-- Recruiters Profile Records
INSERT INTO recruiters (recruiter_id, user_id, company_id, designation, phone, approval_status, approved_at) VALUES
(1, 2, 1, 'Senior Technical Recruiter', '+91 9876543201', 'APPROVED', '2026-01-10 10:00:00'),
(2, 3, 2, 'University Hiring Lead', '+91 9876543202', 'APPROVED', '2026-01-10 10:30:00'),
(3, 4, 3, 'Campus Talent Acquisition', '+91 9876543203', 'APPROVED', '2026-01-11 11:00:00'),
(4, 5, 4, 'HR Manager - Early Careers', '+91 9876543204', 'APPROVED', '2026-01-12 09:00:00'),
(5, 6, 5, 'Regional Recruitment Head', '+91 9876543205', 'APPROVED', '2026-01-12 14:00:00'),
(6, 7, 6, 'Talent Specialist', '+91 9876543206', 'APPROVED', '2026-01-14 15:30:00'),
(7, 8, 7, 'Campus Sourcing Specialist', '+91 9876543207', 'APPROVED', '2026-01-15 11:15:00'),
(8, 9, 8, 'Systems Recruitment Lead', '+91 9876543208', 'APPROVED', '2026-01-16 16:45:00'),
(9, 10, 9, 'Hardware & Firmware Recruiter', '+91 9876543209', 'APPROVED', '2026-01-17 12:00:00'),
(10, 11, 10, 'Lead Tech Recruiter', '+91 9876543210', 'APPROVED', '2026-01-18 10:00:00'),
(11, 12, 11, 'Fintech Talent Partner', '+91 9876543211', 'PENDING', NULL),
(12, 13, 12, 'Cloud Solutions HR', '+91 9876543212', 'PENDING', NULL);

-- --------------------------------------------------------------------
-- 6. STUDENT USERS & PROFILES (Users 14 to 48 -> 35 Students)
-- --------------------------------------------------------------------
INSERT INTO users (user_id, email, password_hash, role, status) VALUES
(14, 'aarav.sharma@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(15, 'diya.patel@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(16, 'rohan.verma@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(17, 'ananya.iyer@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(18, 'kunal.singh@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(19, 'sneha.reddy@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(20, 'aditya.nair@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(21, 'tanvi.joshi@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(22, 'vikram.mehta@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(23, 'ishita.gupta@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(24, 'rahul.deshmukh@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(25, 'pooja.menon@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(26, 'siddharth.roy@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(27, 'neha.kumar@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(28, 'varun.saxena@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(29, 'kritika.bose@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(30, 'akash.mishra@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(31, 'meera.chatterjee@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(32, 'arjun.kapoor@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(33, 'shreya.pandey@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(34, 'nikhil.yadav@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(35, 'priya.shukla@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(36, 'manish.rao@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(37, 'swati.pillai@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(38, 'kavita.kulkarni@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(39, 'gaurav.bhatia@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(40, 'simran.kaur@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(41, 'ayush.tiwari@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(42, 'ritika.das@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(43, 'harsh.jain@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(44, 'bhavna.agarwal@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(45, 'deepak.gupta@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(46, 'anjali.singh@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(47, 'sameer.ali@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE'),
(48, 'pallavi.gowda@student.campus.edu', @DEFAULT_PW, 'STUDENT', 'ACTIVE');

-- Insert 35 Student Profiles
INSERT INTO students (student_id, user_id, roll_number, first_name, last_name, phone, dob, gender, address, branch_id, current_year, cgpa, tenth_percent, twelfth_percent, placement_status) VALUES
(1, 14, '2022CSE001', 'Aarav', 'Sharma', '+91 9811100001', '2002-04-12', 'MALE', '124 MG Road, Bangalore', 1, 4, 9.45, 95.00, 93.50, 'PLACED'),
(2, 15, '2022AIML002', 'Diya', 'Patel', '+91 9811100002', '2002-08-25', 'FEMALE', '54 Ring Road, Ahmedabad', 2, 4, 9.15, 92.40, 91.00, 'PLACED'),
(3, 16, '2022CSE003', 'Rohan', 'Verma', '+91 9811100003', '2001-11-14', 'MALE', '88 Park Street, Kolkata', 1, 4, 8.80, 89.50, 88.00, 'PLACED'),
(4, 17, '2022DS004', 'Ananya', 'Iyer', '+91 9811100004', '2002-02-18', 'FEMALE', '12 Anna Salai, Chennai', 3, 4, 9.30, 94.00, 95.00, 'PLACED'),
(5, 18, '2022IT005', 'Kunal', 'Singh', '+91 9811100005', '2001-09-05', 'MALE', '78 Sector 18, Noida', 4, 4, 8.20, 85.00, 83.50, 'NOT_PLACED'),
(6, 19, '2022ECE006', 'Sneha', 'Reddy', '+91 9811100006', '2002-06-30', 'FEMALE', '33 Banjara Hills, Hyderabad', 5, 4, 8.75, 90.00, 89.00, 'PLACED'),
(7, 20, '2022CSE007', 'Aditya', 'Nair', '+91 9811100007', '2001-12-10', 'MALE', '45 Marine Drive, Kochi', 1, 4, 8.60, 88.00, 86.50, 'NOT_PLACED'),
(8, 21, '2022AIML008', 'Tanvi', 'Joshi', '+91 9811100008', '2002-01-22', 'FEMALE', '19 FC Road, Pune', 2, 4, 8.95, 91.50, 90.00, 'PLACED'),
(9, 22, '2022ME009', 'Vikram', 'Mehta', '+91 9811100009', '2001-07-15', 'MALE', '67 GIDC, Vadodara', 6, 4, 7.80, 82.00, 80.00, 'NOT_PLACED'),
(10, 23, '2022MCA010', 'Ishita', 'Gupta', '+91 9811100010', '2000-03-09', 'FEMALE', '21 Hazratganj, Lucknow', 7, 2, 8.50, 87.00, 85.00, 'NOT_PLACED'),
(11, 24, '2022CSE011', 'Rahul', 'Deshmukh', '+91 9811100011', '2002-05-19', 'MALE', '99 Shivaji Nagar, Nagpur', 1, 4, 7.90, 84.00, 81.50, 'NOT_PLACED'),
(12, 25, '2022IT012', 'Pooja', 'Menon', '+91 9811100012', '2002-10-04', 'FEMALE', '14 MG Road, Trivandrum', 4, 4, 8.40, 86.50, 87.00, 'PLACED'),
(13, 26, '2022DS013', 'Siddharth', 'Roy', '+91 9811100013', '2001-08-16', 'MALE', '51 Salt Lake, Kolkata', 3, 4, 8.65, 89.00, 88.50, 'NOT_PLACED'),
(14, 27, '2022ECE014', 'Neha', 'Kumar', '+91 9811100014', '2002-09-29', 'FEMALE', '40 Bailey Road, Patna', 5, 4, 8.10, 85.50, 83.00, 'NOT_PLACED'),
(15, 28, '2022CSE015', 'Varun', 'Saxena', '+91 9811100015', '2001-06-11', 'MALE', '72 Malviya Nagar, Jaipur', 1, 4, 9.05, 93.00, 91.50, 'PLACED'),
(16, 29, '2022AIML016', 'Kritika', 'Bose', '+91 9811100016', '2002-03-14', 'FEMALE', '10 Southern Avenue, Kolkata', 2, 4, 8.70, 88.50, 89.00, 'NOT_PLACED'),
(17, 30, '2022ME017', 'Akash', 'Mishra', '+91 9811100017', '2001-10-20', 'MALE', '31 Civil Lines, Kanpur', 6, 4, 7.40, 79.00, 78.00, 'NOT_PLACED'),
(18, 31, '2022IT018', 'Meera', 'Chatterjee', '+91 9811100018', '2002-12-01', 'FEMALE', '26 Alipore, Kolkata', 4, 4, 9.20, 94.50, 93.00, 'PLACED'),
(19, 32, '2022CSE019', 'Arjun', 'Kapoor', '+91 9811100019', '2001-04-03', 'MALE', '83 Model Town, Delhi', 1, 4, 8.35, 87.00, 84.00, 'NOT_PLACED'),
(20, 33, '2022DS020', 'Shreya', 'Pandey', '+91 9811100020', '2002-07-28', 'FEMALE', '62 Gomti Nagar, Lucknow', 3, 4, 8.85, 90.50, 89.50, 'PLACED'),
(21, 34, '2022ECE021', 'Nikhil', 'Yadav', '+91 9811100021', '2001-01-15', 'MALE', '94 DLF Phase 3, Gurgaon', 5, 4, 8.25, 83.50, 82.00, 'NOT_PLACED'),
(22, 35, '2022MCA022', 'Priya', 'Shukla', '+91 9811100022', '2000-11-23', 'FEMALE', '15 Kanke Road, Ranchi', 7, 2, 8.15, 85.00, 82.50, 'NOT_PLACED'),
(23, 36, '2022CSE023', 'Manish', 'Rao', '+91 9811100023', '2001-05-17', 'MALE', '48 JP Nagar, Bangalore', 1, 4, 9.50, 96.00, 95.00, 'PLACED'),
(24, 37, '2022IT024', 'Swati', 'Pillai', '+91 9811100024', '2002-02-11', 'FEMALE', '7 Kowdiar, Trivandrum', 4, 4, 7.95, 81.00, 80.50, 'NOT_PLACED'),
(25, 38, '2022AIML025', 'Kavita', 'Kulkarni', '+91 9811100025', '2002-09-08', 'FEMALE', '36 Kothrud, Pune', 2, 4, 8.60, 89.00, 88.00, 'NOT_PLACED'),
(26, 39, '2022ME026', 'Gaurav', 'Bhatia', '+91 9811100026', '2001-08-04', 'MALE', '18 Raja Park, Jaipur', 6, 4, 7.60, 80.00, 79.00, 'NOT_PLACED'),
(27, 40, '2022CSE027', 'Simran', 'Kaur', '+91 9811100027', '2002-06-16', 'FEMALE', '55 Sector 35, Chandigarh', 1, 4, 8.90, 91.00, 92.00, 'PLACED'),
(28, 41, '2022DS028', 'Ayush', 'Tiwari', '+91 9811100028', '2001-10-12', 'MALE', '93 Sigra, Varanasi', 3, 4, 8.05, 84.00, 83.00, 'NOT_PLACED'),
(29, 42, '2022ECE029', 'Ritika', 'Das', '+91 9811100029', '2002-04-27', 'FEMALE', '37 GS Road, Guwahati', 5, 4, 8.45, 87.00, 86.00, 'NOT_PLACED'),
(30, 43, '2022CSE030', 'Harsh', 'Jain', '+91 9811100030', '2001-12-05', 'MALE', '82 South City, Ludhiana', 1, 4, 9.10, 93.50, 91.00, 'PLACED'),
(31, 44, '2022IT031', 'Bhavna', 'Agarwal', '+91 9811100031', '2002-03-31', 'FEMALE', '24 Vijay Nagar, Indore', 4, 4, 8.55, 88.00, 87.50, 'NOT_PLACED'),
(32, 45, '2022AIML032', 'Deepak', 'Gupta', '+91 9811100032', '2001-09-22', 'MALE', '63 Ashok Nagar, Bhopal', 2, 4, 8.40, 86.00, 85.00, 'NOT_PLACED'),
(33, 46, '2022CSE033', 'Anjali', 'Singh', '+91 9811100033', '2002-08-14', 'FEMALE', '11 Hawa Mahal Road, Jaipur', 1, 4, 8.75, 90.00, 89.00, 'NOT_PLACED'),
(34, 47, '2022MCA034', 'Sameer', 'Ali', '+91 9811100034', '2000-05-19', 'MALE', '79 Jamia Nagar, Delhi', 7, 2, 7.85, 82.00, 80.00, 'NOT_PLACED'),
(35, 48, '2022ECE035', 'Pallavi', 'Gowda', '+91 9811100035', '2002-01-08', 'FEMALE', '43 Indiranagar, Bangalore', 5, 4, 8.30, 85.00, 84.50, 'NOT_PLACED');

-- --------------------------------------------------------------------
-- 7. STUDENT SKILLS MAPPING
-- --------------------------------------------------------------------
INSERT INTO student_skills (student_id, skill_id, proficiency_level) VALUES
-- Aarav Sharma (CSE - High Flyer)
(1, 1, 'ADVANCED'), (1, 4, 'EXPERT'), (1, 5, 'EXPERT'), (1, 6, 'ADVANCED'), (1, 7, 'ADVANCED'), (1, 12, 'EXPERT'), (1, 9, 'INTERMEDIATE'),
-- Diya Patel (AIML)
(2, 1, 'EXPERT'), (2, 8, 'EXPERT'), (2, 14, 'ADVANCED'), (2, 19, 'ADVANCED'), (2, 7, 'INTERMEDIATE'), (2, 11, 'ADVANCED'),
-- Rohan Verma (CSE)
(3, 2, 'ADVANCED'), (3, 7, 'ADVANCED'), (3, 13, 'ADVANCED'), (3, 9, 'INTERMEDIATE'), (3, 10, 'INTERMEDIATE'), (3, 12, 'ADVANCED'),
-- Ananya Iyer (Data Science)
(4, 1, 'EXPERT'), (4, 7, 'EXPERT'), (4, 8, 'ADVANCED'), (4, 17, 'ADVANCED'), (4, 11, 'ADVANCED'),
-- Kunal Singh (IT)
(5, 4, 'INTERMEDIATE'), (5, 15, 'ADVANCED'), (5, 6, 'INTERMEDIATE'), (5, 7, 'INTERMEDIATE'),
-- Sneha Reddy (ECE)
(6, 1, 'ADVANCED'), (6, 3, 'ADVANCED'), (6, 16, 'ADVANCED'), (6, 11, 'INTERMEDIATE'),
-- Aditya Nair (CSE)
(7, 2, 'ADVANCED'), (7, 4, 'ADVANCED'), (7, 5, 'INTERMEDIATE'), (7, 7, 'INTERMEDIATE'), (7, 12, 'ADVANCED'),
-- Tanvi Joshi (AIML)
(8, 1, 'EXPERT'), (8, 8, 'ADVANCED'), (8, 14, 'ADVANCED'), (8, 7, 'INTERMEDIATE'), (8, 11, 'ADVANCED'),
-- Vikram Mehta (ME)
(9, 1, 'INTERMEDIATE'), (9, 3, 'INTERMEDIATE'), (9, 16, 'BEGINNER'),
-- Ishita Gupta (MCA)
(10, 2, 'ADVANCED'), (10, 7, 'ADVANCED'), (10, 13, 'INTERMEDIATE'), (10, 4, 'INTERMEDIATE'),
-- Varun Saxena (CSE)
(15, 1, 'ADVANCED'), (15, 4, 'EXPERT'), (15, 5, 'ADVANCED'), (15, 6, 'ADVANCED'), (15, 9, 'INTERMEDIATE'), (15, 10, 'INTERMEDIATE'),
-- Meera Chatterjee (IT)
(18, 4, 'EXPERT'), (18, 5, 'EXPERT'), (18, 6, 'ADVANCED'), (18, 7, 'ADVANCED'), (18, 11, 'ADVANCED'),
-- Shreya Pandey (DS)
(20, 1, 'EXPERT'), (20, 8, 'ADVANCED'), (20, 7, 'ADVANCED'), (20, 17, 'ADVANCED'),
-- Manish Rao (CSE)
(23, 1, 'EXPERT'), (23, 2, 'EXPERT'), (23, 3, 'EXPERT'), (23, 12, 'EXPERT'), (23, 7, 'ADVANCED'), (23, 9, 'ADVANCED'),
-- Simran Kaur (CSE)
(27, 4, 'EXPERT'), (27, 5, 'ADVANCED'), (27, 6, 'ADVANCED'), (27, 7, 'ADVANCED'),
-- Harsh Jain (CSE)
(30, 1, 'ADVANCED'), (30, 2, 'ADVANCED'), (30, 12, 'EXPERT'), (30, 10, 'ADVANCED');

-- --------------------------------------------------------------------
-- 8. STUDENT PROJECTS
-- --------------------------------------------------------------------
INSERT INTO projects (project_id, student_id, title, description, technologies, github_url, live_url) VALUES
(1, 1, 'Cloud Native Microservices Portal', 'Scalable microservices platform built with Docker, Node.js and React.', 'React, Node.js, Docker, MongoDB', 'https://github.com/aarav/microservices-demo', 'https://demo.aarav.dev'),
(2, 2, 'Medical Imaging Lung Lesion Classifier', 'Deep learning pipeline with CNNs achieving 96% accuracy on CT scan datasets.', 'Python, TensorFlow, OpenCV, Flask', 'https://github.com/diya/med-imaging', 'https://medai.example.org'),
(3, 3, 'High Throughput Banking Ledger', 'Concurrent transaction ledger in Spring Boot with distributed locking.', 'Java, Spring Boot, PostgreSQL, Redis', 'https://github.com/rohan/bank-ledger', NULL),
(4, 4, 'Customer Churn Predictive Pipeline', 'End-to-end data pipeline processing 1M records with XGBoost and Pandas.', 'Python, Pandas, Scikit-Learn, Streamlit', 'https://github.com/ananya/churn-prediction', 'https://churn-analytics.ai'),
(5, 6, 'RTOS Embedded Sensor Gateway', 'Firmware in C++ running FreeRTOS transmitting telemetry over MQTT.', 'C++, FreeRTOS, ESP32, MQTT', 'https://github.com/sneha/rtos-gateway', NULL),
(6, 15, 'Realtime Collaborative Whiteboard', 'WebSocket canvas application with vector rendering and session replay.', 'JavaScript, Canvas API, Socket.io, Node.js', 'https://github.com/varun/whiteboard', 'https://draw.varun.dev'),
(7, 18, 'Enterprise E-Commerce Engine', 'Serverless shopping portal with Stripe integration and dynamic search.', 'React, Next.js, Node.js, PostgreSQL', 'https://github.com/meera/ecommerce', 'https://shop.meera.com'),
(8, 23, 'Distributed Key-Value Store', 'Raft-consensus based replicated storage engine in C++ and Go.', 'C++, Raft, gRPC, Linux', 'https://github.com/manish/kvstore', NULL);

-- --------------------------------------------------------------------
-- 9. STUDENT CERTIFICATIONS
-- --------------------------------------------------------------------
INSERT INTO certifications (cert_id, student_id, title, issuing_org, issue_date, credential_url) VALUES
(1, 1, 'AWS Certified Solutions Architect - Associate', 'Amazon Web Services', '2025-06-15', 'https://aws.amazon.com/verify/1001'),
(2, 2, 'TensorFlow Developer Certificate', 'Google Developers', '2025-04-10', 'https://google.com/cert/tf2002'),
(3, 3, 'Oracle Certified Professional: Java SE 17 Developer', 'Oracle', '2025-05-20', 'https://oracle.com/verify/3003'),
(4, 4, 'Microsoft Certified: Azure Data Scientist Associate', 'Microsoft', '2025-08-12', 'https://microsoft.com/cert/azure4004'),
(5, 6, 'Certified Kubernetes Application Developer (CKAD)', 'Cloud Native Computing Foundation', '2025-07-01', 'https://cncf.io/ckad/5005'),
(6, 15, 'Meta Front-End Developer Professional Certificate', 'Coursera / Meta', '2025-03-22', 'https://coursera.org/meta/frontend6006'),
(7, 18, 'Google Cloud Certified Professional Cloud Developer', 'Google Cloud', '2025-09-05', 'https://cloud.google.com/cert/7007'),
(8, 23, 'Red Hat Certified System Administrator (RHCSA)', 'Red Hat', '2025-02-18', 'https://redhat.com/verify/8008');

-- --------------------------------------------------------------------
-- 10. JOBS & INTERNSHIPS (22 Listings across Companies)
-- --------------------------------------------------------------------
INSERT INTO jobs (job_id, company_id, posted_by_recruiter_id, title, description, job_type, work_mode, location, min_cgpa, salary_stipend, experience_req, deadline, openings_count, status) VALUES
(1, 1, 1, 'Software Engineer - University Graduate', 'Build large-scale distributed systems and web infrastructure at Google.', 'FULL_TIME', 'HYBRID', 'Bangalore, Karnataka', 8.50, 3200000.00, 'Fresher', '2026-11-30', 5, 'LIVE'),
(2, 1, 1, 'Machine Learning Research Intern', 'Work with Google DeepMind researchers on vision and language models.', 'INTERNSHIP', 'ON_SITE', 'Bangalore, Karnataka', 8.50, 90000.00, 'Intern', '2026-10-15', 3, 'LIVE'),
(3, 2, 2, 'Software Development Engineer (SDE-1)', 'Core Windows, Azure and Office cloud backend engineering roles.', 'FULL_TIME', 'HYBRID', 'Hyderabad, Telangana', 8.00, 2400000.00, 'Fresher', '2026-11-20', 8, 'LIVE'),
(4, 2, 2, 'Cloud Engineering Intern', 'Three-month summer internship on Azure Virtual Machines & Kubernetes.', 'INTERNSHIP', 'REMOTE', 'Hyderabad, Telangana', 7.50, 75000.00, 'Intern', '2026-10-31', 6, 'LIVE'),
(5, 3, 3, 'Software Development Engineer I', 'Design fault-tolerant services handling millions of customer orders daily.', 'FULL_TIME', 'ON_SITE', 'Bangalore, Karnataka', 7.50, 2800000.00, 'Fresher', '2026-12-15', 10, 'LIVE'),
(6, 3, 3, 'Data Analyst Intern', 'Analyze customer traffic and delivery telemetry using SQL and Python.', 'INTERNSHIP', 'HYBRID', 'Bangalore, Karnataka', 7.00, 60000.00, 'Intern', '2026-10-25', 4, 'LIVE'),
(7, 4, 4, 'Systems Engineer - Digital Specialist', 'Full stack enterprise web applications in modern Java and JavaScript.', 'FULL_TIME', 'HYBRID', 'Pune, Maharashtra', 6.50, 950000.00, 'Fresher', '2026-12-31', 25, 'LIVE'),
(8, 4, 4, 'Associate Software Developer', 'Entry-level developer program with rigorous onboarding training.', 'FULL_TIME', 'ON_SITE', 'Bangalore, Karnataka', 6.00, 650000.00, 'Fresher', '2026-12-31', 40, 'LIVE'),
(9, 5, 5, 'TCS Digital Software Developer', 'Next-generation digital project delivery using Cloud, AI, and Big Data.', 'FULL_TIME', 'HYBRID', 'Mumbai, Maharashtra', 7.00, 750000.00, 'Fresher', '2026-12-20', 30, 'LIVE'),
(10, 5, 5, 'TCS Ninja Developer', 'Core software engineering role across multinational banking clients.', 'FULL_TIME', 'ON_SITE', 'Pune, Maharashtra', 6.00, 420000.00, 'Fresher', '2026-12-31', 50, 'LIVE'),
(11, 6, 6, 'Project Engineer - Turbo', 'High-performance engineering track with mentorship and global rotation.', 'FULL_TIME', 'HYBRID', 'Bangalore, Karnataka', 6.50, 650000.00, 'Fresher', '2026-12-15', 20, 'LIVE'),
(12, 7, 7, 'Advanced Application Engineering Analyst', 'DevOps, cloud migration, and Salesforce enterprise integration.', 'FULL_TIME', 'HYBRID', 'Gurgaon, Haryana', 6.50, 850000.00, 'Fresher', '2026-12-10', 15, 'LIVE'),
(13, 8, 8, 'Software Engineer - Networking & Cloud', 'Build next-generation routing, switching, and cloud security platforms.', 'FULL_TIME', 'ON_SITE', 'Bangalore, Karnataka', 7.50, 1800000.00, 'Fresher', '2026-11-25', 5, 'LIVE'),
(14, 9, 9, 'Modem Firmware Engineer', 'Low-level embedded C++ programming for 5G Snapdragon baseband modems.', 'FULL_TIME', 'ON_SITE', 'Hyderabad, Telangana', 8.00, 2000000.00, 'Fresher', '2026-11-15', 4, 'LIVE'),
(15, 10, 10, 'Member of Technical Staff - Creative Cloud', 'Web rendering, canvas manipulation, and cloud asset synchronization.', 'FULL_TIME', 'HYBRID', 'Noida, Uttar Pradesh', 8.50, 2600000.00, 'Fresher', '2026-11-18', 4, 'LIVE'),
(16, 11, NULL, 'Advisory Tech Consultant', 'Enterprise cybersecurity and financial analytics software systems.', 'FULL_TIME', 'ON_SITE', 'Hyderabad, Telangana', 7.00, 1100000.00, 'Fresher', '2026-12-05', 8, 'PENDING'),
(17, 12, NULL, 'Quantum AI Research Fellow', 'Researching variational quantum algorithms for scientific computation.', 'FULL_TIME', 'REMOTE', 'Bangalore, Karnataka', 8.50, 2200000.00, 'Fresher', '2026-12-12', 2, 'PENDING'),
(18, 1, 1, 'Site Reliability Engineering Intern', 'Automate telemetry, fault injection, and multi-region failover.', 'INTERNSHIP', 'HYBRID', 'Bangalore, Karnataka', 8.00, 80000.00, 'Intern', '2026-10-20', 2, 'LIVE'),
(19, 2, 2, 'Frontend Developer (Edge & Web)', 'React and TypeScript engineering on browser platforms and extensions.', 'FULL_TIME', 'REMOTE', 'Hyderabad, Telangana', 7.50, 1600000.00, 'Fresher', '2026-11-28', 5, 'LIVE'),
(20, 3, 3, 'DevOps & Infrastructure Engineer', 'Terraform and AWS infrastructure automation for Prime Video streaming.', 'FULL_TIME', 'ON_SITE', 'Bangalore, Karnataka', 7.50, 2100000.00, 'Fresher', '2026-12-01', 6, 'LIVE');

-- --------------------------------------------------------------------
-- 11. JOB REQUIRED SKILLS MAPPING
-- --------------------------------------------------------------------
INSERT INTO job_skills (job_id, skill_id, is_required) VALUES
-- Job 1: Google SDE
(1, 3, 1), (1, 12, 1), (1, 7, 1), (1, 1, 0),
-- Job 2: Google ML Intern
(2, 1, 1), (2, 8, 1), (2, 14, 1), (2, 19, 0),
-- Job 3: Microsoft SDE
(3, 2, 1), (3, 12, 1), (3, 7, 1), (3, 9, 0),
-- Job 4: Microsoft Cloud Intern
(4, 10, 1), (4, 9, 1), (4, 16, 1),
-- Job 5: Amazon SDE
(5, 2, 1), (5, 12, 1), (5, 6, 0), (5, 10, 0),
-- Job 6: Amazon Data Analyst
(6, 1, 1), (6, 7, 1), (6, 8, 0),
-- Job 7: Infosys Digital
(7, 2, 1), (7, 4, 1), (7, 7, 1), (7, 15, 1),
-- Job 8: Infosys Associate
(8, 2, 1), (8, 7, 1),
-- Job 9: TCS Digital
(9, 1, 1), (9, 7, 1), (9, 12, 0),
-- Job 13: Cisco Networking
(13, 3, 1), (13, 16, 1), (13, 1, 0),
-- Job 14: Qualcomm Modem
(14, 3, 1), (14, 16, 1), (14, 12, 1),
-- Job 15: Adobe MTS
(15, 4, 1), (15, 5, 1), (15, 12, 1), (15, 3, 0),
-- Job 19: Microsoft Frontend
(19, 4, 1), (19, 5, 1), (19, 15, 1),
-- Job 20: Amazon DevOps
(20, 9, 1), (20, 10, 1), (20, 16, 1);

-- --------------------------------------------------------------------
-- 12. JOB ELIGIBLE BRANCHES
-- --------------------------------------------------------------------
INSERT INTO job_eligible_branches (job_id, branch_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), -- Google SDE: CSE, AIML, DS, IT
(2, 1), (2, 2), (2, 3),         -- Google ML: CSE, AIML, DS
(3, 1), (3, 2), (3, 3), (3, 4), (3, 7), -- Microsoft: CSE, AIML, DS, IT, MCA
(4, 1), (4, 4), (4, 5), (4, 7),
(5, 1), (5, 2), (5, 3), (5, 4),
(6, 1), (6, 2), (6, 3), (6, 4), (6, 7),
(7, 1), (7, 2), (7, 3), (7, 4), (7, 5), (7, 7),
(8, 1), (8, 2), (8, 3), (8, 4), (8, 5), (8, 6), (8, 7), -- All branches
(9, 1), (9, 2), (9, 3), (9, 4), (9, 5), (9, 7),
(13, 1), (13, 4), (13, 5),      -- Cisco: CSE, IT, ECE
(14, 1), (14, 5),               -- Qualcomm: CSE, ECE
(15, 1), (15, 2), (15, 4);      -- Adobe: CSE, AIML, IT

-- --------------------------------------------------------------------
-- 13. APPLICATIONS (55+ Real records across statuses)
-- --------------------------------------------------------------------
INSERT INTO applications (application_id, job_id, student_id, applied_at, status, notes) VALUES
-- Placed / Selected Applications
(1, 1, 1, '2026-02-01 09:15:00', 'SELECTED', 'Offered SDE position with 32 LPA package.'),
(2, 2, 2, '2026-02-02 11:20:00', 'SELECTED', 'Offered ML Research Intern stipend 90k/month.'),
(3, 3, 3, '2026-02-03 14:00:00', 'SELECTED', 'Offered SDE-1 position with 24 LPA package.'),
(4, 6, 4, '2026-02-04 10:45:00', 'SELECTED', 'Selected as Data Analyst Intern.'),
(5, 14, 6, '2026-02-05 16:30:00', 'SELECTED', 'Offered Firmware Engineer with 20 LPA package.'),
(6, 2, 8, '2026-02-06 13:10:00', 'SELECTED', 'Selected for Google ML Internship.'),
(7, 7, 12, '2026-02-07 10:00:00', 'SELECTED', 'Selected as Infosys Digital Specialist.'),
(8, 15, 15, '2026-02-08 12:40:00', 'SELECTED', 'Offered MTS role at Adobe with 26 LPA.'),
(9, 19, 18, '2026-02-09 15:20:00', 'SELECTED', 'Offered Microsoft Frontend role with 16 LPA.'),
(10, 6, 20, '2026-02-10 11:00:00', 'SELECTED', 'Selected for Amazon Analytics.'),
(11, 1, 23, '2026-02-11 09:30:00', 'SELECTED', 'Offered Google SDE with 32 LPA package.'),
(12, 7, 27, '2026-02-12 14:15:00', 'SELECTED', 'Selected as Digital Specialist at Infosys.'),
(13, 5, 30, '2026-02-13 17:00:00', 'SELECTED', 'Offered Amazon SDE role with 28 LPA.'),

-- In-flight: INTERVIEW_SCHEDULED
(14, 1, 15, '2026-02-14 10:00:00', 'INTERVIEW_SCHEDULED', 'Technical Round 2 scheduled.'),
(15, 3, 1, '2026-02-14 11:30:00', 'INTERVIEW_SCHEDULED', 'System Design round pending.'),
(16, 5, 7, '2026-02-15 09:45:00', 'INTERVIEW_SCHEDULED', 'Coding Round passed, HR interview scheduled.'),
(17, 3, 13, '2026-02-15 14:00:00', 'INTERVIEW_SCHEDULED', 'Technical interview scheduled.'),
(18, 13, 14, '2026-02-16 11:15:00', 'INTERVIEW_SCHEDULED', 'Hardware & C++ round scheduled.'),
(19, 20, 19, '2026-02-16 16:30:00', 'INTERVIEW_SCHEDULED', 'DevOps assessment interview.'),
(20, 15, 24, '2026-02-17 10:30:00', 'INTERVIEW_SCHEDULED', 'UI/UX architecture round.'),

-- In-flight: SHORTLISTED
(21, 1, 4, '2026-02-18 10:15:00', 'SHORTLISTED', 'Resume shortlisted by engineering team.'),
(22, 3, 7, '2026-02-18 11:45:00', 'SHORTLISTED', 'Shortlisted based on CGPA and coding score.'),
(23, 5, 11, '2026-02-19 14:10:00', 'SHORTLISTED', 'Shortlisted for online assessment.'),
(24, 7, 5, '2026-02-19 16:00:00', 'SHORTLISTED', 'Shortlisted for technical test.'),
(25, 9, 10, '2026-02-20 09:20:00', 'SHORTLISTED', 'Profile matched MCA criteria.'),
(26, 12, 17, '2026-02-20 13:40:00', 'SHORTLISTED', 'Shortlisted for consulting round.'),
(27, 13, 21, '2026-02-21 15:00:00', 'SHORTLISTED', 'Shortlisted for Cisco technical test.'),
(28, 19, 27, '2026-02-21 17:30:00', 'SHORTLISTED', 'Frontend portfolio approved.'),

-- In-flight: UNDER_REVIEW
(29, 1, 7, '2026-02-22 09:00:00', 'UNDER_REVIEW', 'Application under review by recruiter.'),
(30, 3, 16, '2026-02-22 10:30:00', 'UNDER_REVIEW', 'Reviewing ML background.'),
(31, 5, 18, '2026-02-23 11:15:00', 'UNDER_REVIEW', 'Under recruiter review.'),
(32, 7, 22, '2026-02-23 14:00:00', 'UNDER_REVIEW', 'Checking transcript.'),
(33, 9, 25, '2026-02-24 15:45:00', 'UNDER_REVIEW', 'Evaluating aptitude test.'),
(34, 11, 26, '2026-02-24 16:30:00', 'UNDER_REVIEW', 'Mechanical branch background verification.'),
(35, 14, 29, '2026-02-25 10:00:00', 'UNDER_REVIEW', 'Evaluating embedded projects.'),

-- Pure APPLIED
(36, 1, 16, '2026-02-25 12:00:00', 'APPLIED', 'Application submitted.'),
(37, 2, 4, '2026-02-25 14:30:00', 'APPLIED', 'Application submitted.'),
(38, 4, 5, '2026-02-26 09:15:00', 'APPLIED', 'Application submitted.'),
(39, 5, 23, '2026-02-26 10:45:00', 'APPLIED', 'Application submitted.'),
(40, 7, 10, '2026-02-26 13:20:00', 'APPLIED', 'Application submitted.'),
(41, 8, 9, '2026-02-27 11:00:00', 'APPLIED', 'Application submitted.'),
(42, 9, 11, '2026-02-27 15:30:00', 'APPLIED', 'Application submitted.'),
(43, 10, 17, '2026-02-28 09:40:00', 'APPLIED', 'Application submitted.'),
(44, 11, 31, '2026-02-28 14:10:00', 'APPLIED', 'Application submitted.'),
(45, 12, 32, '2026-03-01 10:00:00', 'APPLIED', 'Application submitted.'),
(46, 13, 35, '2026-03-01 16:00:00', 'APPLIED', 'Application submitted.'),
(47, 15, 33, '2026-03-02 11:30:00', 'APPLIED', 'Application submitted.'),
(48, 20, 34, '2026-03-02 15:00:00', 'APPLIED', 'Application submitted.'),

-- REJECTED / WITHDRAWN
(49, 1, 9, '2026-02-05 10:00:00', 'REJECTED', 'Did not satisfy minimum CGPA requirement.'),
(50, 1, 10, '2026-02-06 11:00:00', 'REJECTED', 'Branch not eligible for this track.'),
(51, 3, 9, '2026-02-07 14:00:00', 'REJECTED', 'Did not meet technical threshold.'),
(52, 5, 17, '2026-02-08 09:30:00', 'REJECTED', 'Failed coding assessment round 1.'),
(53, 14, 18, '2026-02-09 16:00:00', 'REJECTED', 'Skill requirements not aligned.'),
(54, 7, 1, '2026-02-10 12:00:00', 'WITHDRAWN', 'Candidate accepted another offer.'),
(55, 9, 3, '2026-02-11 13:30:00', 'WITHDRAWN', 'Student opted out after Tier-1 selection.');

-- --------------------------------------------------------------------
-- 14. INTERVIEWS (Historical & Upcoming)
-- --------------------------------------------------------------------
INSERT INTO interviews (interview_id, application_id, round_name, scheduled_at, meeting_link_location, interviewer_feedback, status, result) VALUES
-- Placed students' completed interviews
(1, 1, 'Technical Round 1 (DSA)', '2026-02-03 10:00:00', 'https://meet.google.com/abc-defg-hij', 'Exceptional graph theory and dynamic programming problem solving.', 'COMPLETED', 'PASSED'),
(2, 1, 'Technical Round 2 (System Design)', '2026-02-05 14:00:00', 'https://meet.google.com/xyz-uvwx-rst', 'Clean high level architecture, strong distributed cache knowledge.', 'COMPLETED', 'PASSED'),
(3, 1, 'Managerial & Cultural Fit', '2026-02-07 16:00:00', 'https://meet.google.com/gog-corp-demo', 'Great communication and leadership qualities.', 'COMPLETED', 'PASSED'),
(4, 2, 'ML Coding & Math Round', '2026-02-04 11:00:00', 'https://meet.google.com/deep-mind-int', 'Very solid linear algebra and backprop fundamentals.', 'COMPLETED', 'PASSED'),
(5, 3, 'Azure Systems Round', '2026-02-06 10:30:00', 'https://teams.microsoft.com/l/meetup/1234', 'Proficient in Java multi-threading and OS memory models.', 'COMPLETED', 'PASSED'),
(6, 5, 'Embedded Hardware Round', '2026-02-08 14:00:00', 'https://qualcomm.webex.com/meet/recruiter', 'Expert knowledge of C pointers and microcontrollers.', 'COMPLETED', 'PASSED'),
(7, 8, 'UI/UX & Creative Canvas Round', '2026-02-10 11:00:00', 'https://adobe.zoom.us/j/9876543210', 'Impressive WebGL and DOM optimization expertise.', 'COMPLETED', 'PASSED'),
(8, 11, 'Google Systems Round', '2026-02-13 15:00:00', 'https://meet.google.com/manish-sde-round', 'Flawless code with optimal time and space complexity.', 'COMPLETED', 'PASSED'),

-- Active scheduled upcoming interviews
(9, 14, 'Google Technical Round 2', '2026-10-10 11:00:00', 'https://meet.google.com/gog-rnd2-varun', 'Round 1 went very well, moving to round 2.', 'SCHEDULED', 'PENDING'),
(10, 15, 'Microsoft System Design Round', '2026-10-12 14:30:00', 'https://teams.microsoft.com/l/meetup/ms-aarav', 'High scale distributed concepts assessment.', 'SCHEDULED', 'PENDING'),
(11, 16, 'Amazon HR & Behavioral', '2026-10-14 10:00:00', 'https://amazon.chime.aws/aditya-hr', 'Leadership Principles evaluation round.', 'SCHEDULED', 'PENDING'),
(12, 17, 'Microsoft Technical Interview', '2026-10-15 16:00:00', 'https://teams.microsoft.com/l/meetup/ms-siddharth', 'Algorithms and data structures.', 'SCHEDULED', 'PENDING'),
(13, 18, 'Cisco Network Protocols Round', '2026-10-16 11:30:00', 'https://cisco.webex.com/meet/neha-tech', 'TCP/IP and routing algorithms.', 'SCHEDULED', 'PENDING');

-- --------------------------------------------------------------------
-- 15. PLACEMENTS (13 Official Offers with packages)
-- --------------------------------------------------------------------
INSERT INTO placements (placement_id, application_id, student_id, company_id, job_id, package_lpa, offer_letter_path, accepted_at) VALUES
(1, 1, 1, 1, 1, 32.00, 'assets/uploads/offers/offer_aarav_google.pdf', '2026-02-08 17:30:00'),
(2, 2, 2, 1, 2, 10.80, 'assets/uploads/offers/offer_diya_google.pdf', '2026-02-07 12:00:00'),
(3, 3, 3, 2, 3, 24.00, 'assets/uploads/offers/offer_rohan_microsoft.pdf', '2026-02-09 15:00:00'),
(4, 4, 4, 3, 6, 7.20, 'assets/uploads/offers/offer_ananya_amazon.pdf', '2026-02-10 11:45:00'),
(5, 5, 6, 9, 14, 20.00, 'assets/uploads/offers/offer_sneha_qualcomm.pdf', '2026-02-11 18:00:00'),
(6, 6, 8, 1, 2, 10.80, 'assets/uploads/offers/offer_tanvi_google.pdf', '2026-02-12 10:15:00'),
(7, 7, 12, 4, 7, 9.50, 'assets/uploads/offers/offer_pooja_infosys.pdf', '2026-02-12 16:30:00'),
(8, 8, 15, 10, 15, 26.00, 'assets/uploads/offers/offer_varun_adobe.pdf', '2026-02-13 14:00:00'),
(9, 9, 18, 2, 19, 16.00, 'assets/uploads/offers/offer_meera_microsoft.pdf', '2026-02-14 11:20:00'),
(10, 10, 20, 3, 6, 7.20, 'assets/uploads/offers/offer_shreya_amazon.pdf', '2026-02-15 13:00:00'),
(11, 11, 23, 1, 1, 32.00, 'assets/uploads/offers/offer_manish_google.pdf', '2026-02-15 17:00:00'),
(12, 12, 27, 4, 7, 9.50, 'assets/uploads/offers/offer_simran_infosys.pdf', '2026-02-16 12:30:00'),
(13, 13, 30, 3, 5, 28.00, 'assets/uploads/offers/offer_harsh_amazon.pdf', '2026-02-17 10:45:00');

-- --------------------------------------------------------------------
-- 16. INITIAL NOTIFICATIONS (For demoing notification bells)
-- --------------------------------------------------------------------
INSERT INTO notifications (user_id, title, message, is_read, link_url) VALUES
(14, 'Offer Letter Received!', 'Congratulations Aarav! Google has officially extended your full-time SDE offer of 32 LPA.', 1, 'placement.html'),
(15, 'Interview Confirmed', 'Your Google ML research interview has been scheduled.', 1, 'interviews.html'),
(16, 'Offer Letter Generated', 'Microsoft has issued your SDE-1 offer of 24 LPA.', 1, 'placement.html'),
(1, 'New Job Pending Approval', 'Deloitte has posted a new job: Advisory Tech Consultant.', 0, 'jobs.html'),
(1, 'New Recruiter Pending Verification', 'Recruiter from FinTech Co is awaiting verification.', 0, 'recruiters.html'),
(2, 'New Application Received', 'Varun Saxena applied for Software Engineer position.', 0, 'applicants.html'),
(3, 'New Application Received', 'Aarav Sharma applied for Software Development Engineer I.', 0, 'applicants.html');
