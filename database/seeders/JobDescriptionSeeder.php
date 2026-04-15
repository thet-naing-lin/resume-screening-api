<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JobDescriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Get HR user IDs (assumes UserSeeder runs first)
        $hrUserId    = DB::table('users')->where('email', 'hr@resumescreening.com')->value('id');
        $adminUserId = DB::table('users')->where('email', 'admin@resumescreening.com')->value('id');

        $now = Carbon::now();

        $jobs = [
            // ─── 1. Software Engineer ───────────────────────────────────────
            [
                'user_id'                  => $hrUserId,
                'title'                    => 'Software Engineer',
                'description'              => 'We are looking for a skilled Software Engineer to develop and maintain web applications. You will work with a cross-functional team to deliver high-quality software solutions. The ideal candidate has strong problem-solving skills and experience with modern web frameworks.',
                'required_skills'          => json_encode(['PHP', 'Laravel', 'React', 'MySQL', 'REST API', 'Git', 'HTML', 'CSS', 'JavaScript']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Computer Science or related field", 'Strong understanding of OOP principles', 'Experience with version control systems']),
                'experience_level'         => 'mid',
                // 'experience_years'         => 2,
                'employment_type'          => 'full-time',
                'location'                 => 'Yangon, Myanmar',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 2. Frontend Developer ──────────────────────────────────────
            [
                'user_id'                  => $hrUserId,
                'title'                    => 'Frontend Developer',
                'description'              => 'We are seeking a creative and detail-oriented Frontend Developer to build responsive and user-friendly web interfaces. You will collaborate closely with designers and backend developers to implement pixel-perfect UI components.',
                'required_skills'          => json_encode(['React', 'JavaScript', 'TypeScript', 'Tailwind CSS', 'HTML5', 'CSS3', 'Axios', 'Git', 'Figma']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Computer Science, Design, or related field", 'Strong portfolio of frontend projects', 'Understanding of responsive design principles']),
                'experience_level'         => 'junior',
                // 'experience_years'         => 1,
                'employment_type'          => 'part-time',
                'location'                 => 'Remote',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 3. Backend Developer ───────────────────────────────────────
            [
                'user_id'                  => $hrUserId,
                'title'                    => 'Backend Developer',
                'description'              => 'We need an experienced Backend Developer to design and implement scalable server-side applications and APIs. You will work with databases, authentication systems, and third-party integrations to deliver robust backend solutions.',
                'required_skills'          => json_encode(['PHP', 'Laravel', 'MySQL', 'Redis', 'REST API', 'Queue Management', 'Docker', 'Sanctum', 'PHPUnit']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Computer Science or Engineering", 'Experience building RESTful APIs', 'Knowledge of database design and optimization']),
                'experience_level'         => 'mid',
                // 'experience_years'         => 3,
                'employment_type'          => 'contract',
                'location'                 => 'Yangon, Myanmar',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 4. Data Analyst ────────────────────────────────────────────
            [
                'user_id'                  => $adminUserId,
                'title'                    => 'Data Analyst',
                'description'              => 'We are looking for a Data Analyst to collect, process, and interpret large datasets to help drive business decisions. The ideal candidate will use statistical tools and visualization techniques to present data-driven insights to stakeholders.',
                'required_skills'          => json_encode(['Python', 'SQL', 'Excel', 'Power BI', 'Pandas', 'NumPy', 'Data Visualization', 'Statistical Analysis', 'Tableau']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Statistics, Mathematics, or Computer Science", 'Experience with data analysis and reporting tools', 'Strong analytical and communication skills']),
                'experience_level'         => 'mid',
                // 'experience_years'         => 2,
                'employment_type'          => 'internship',
                'location'                 => 'Yangon, Myanmar',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 5. HR Manager ──────────────────────────────────────────────
            [
                'user_id'                  => $adminUserId,
                'title'                    => 'HR Manager',
                'description'              => 'We are seeking an experienced HR Manager to oversee recruitment, employee relations, and performance management. The ideal candidate will develop HR strategies and ensure compliance with employment laws.',
                'required_skills'          => json_encode(['Recruitment', 'Employee Relations', 'Performance Management', 'HR Software', 'Communication', 'Conflict Resolution', 'Training & Development', 'Labour Law']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Human Resources or Business Administration", 'Professional HR certification (CIPD or equivalent)', 'Experience managing full recruitment lifecycle']),
                'experience_level'         => 'senior',
                // 'experience_years'         => 5,
                'employment_type'          => 'full-time',
                'location'                 => 'Yangon, Myanmar',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 6. DevOps Engineer ─────────────────────────────────────────
            [
                'user_id'                  => $hrUserId,
                'title'                    => 'DevOps Engineer',
                'description'              => 'We need a DevOps Engineer to manage CI/CD pipelines, cloud infrastructure, and deployment automation. You will ensure system reliability, scalability, and security across all environments.',
                'required_skills'          => json_encode(['Docker', 'Kubernetes', 'CI/CD', 'Linux', 'Nginx', 'AWS', 'Git', 'Jenkins', 'Terraform', 'Monitoring']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Computer Science or related field", 'AWS or Azure cloud certification preferred', 'Experience with containerization and orchestration']),
                'experience_level'         => 'senior',
                // 'experience_years'         => 4,
                'employment_type'          => 'full-time',
                'location'                 => 'Remote',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 7. Junior Web Developer (DRAFT) ───────────────────────────
            [
                'user_id'                  => $hrUserId,
                'title'                    => 'Junior Web Developer',
                'description'              => 'An exciting junior-level opportunity for a Junior Web Developer to join our growing team. You will assist in building web applications, fixing bugs, and learning from senior developers in an Agile environment.',
                'required_skills'          => json_encode(['HTML', 'CSS', 'JavaScript', 'PHP', 'MySQL', 'Git', 'Bootstrap']),
                // 'required_qualifications'  => json_encode(["Diploma or Bachelor's degree in IT or related field", 'Basic understanding of web technologies', 'Willingness to learn and adapt']),
                'experience_level'         => 'junior',
                // 'experience_years'         => 0,
                'employment_type'          => 'full-time',
                'location'                 => 'Yangon, Myanmar',
                'status'                   => 'active',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],

            // ─── 8. UI/UX Designer ──────────────────────────────────────────
            [
                'user_id'                  => $adminUserId,
                'title'                    => 'UI/UX Designer',
                'description'              => 'We are looking for a UI/UX Designer to create intuitive and visually appealing digital experiences. You will conduct user research, build wireframes, and collaborate with developers to bring designs to life.',
                'required_skills'          => json_encode(['Figma', 'Adobe XD', 'Prototyping', 'Wireframing', 'User Research', 'Design Systems', 'CSS', 'Responsive Design', 'Accessibility']),
                // 'required_qualifications'  => json_encode(["Bachelor's degree in Design, HCI, or related field", 'Strong portfolio demonstrating UX process', 'Understanding of WCAG accessibility standards']),
                'experience_level'         => 'mid',
                // 'experience_years'         => 2,
                'employment_type'          => 'full-time',
                'location'                 => 'Yangon, Myanmar',
                'status'                   => 'closed',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
        ];

        DB::table('job_descriptions')->insert($jobs);

        $this->command->info('✅ JobDescriptionSeeder: 8 job descriptions seeded successfully.');
    }
}