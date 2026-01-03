<?php
/**
 * Seed: Sample Applicants Data
 * Creates sample grades, intake campaigns, and applicants for testing
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Seeding Applicants Sample Data\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();

    // 1. Seed Grades
    echo "1. Seeding grades...\n";
    $grades = [
        ['PP1', 0, 'Pre-Primary', 3, 4],
        ['PP2', 1, 'Pre-Primary', 4, 5],
        ['Grade 1', 2, 'Primary', 5, 7],
        ['Grade 2', 3, 'Primary', 7, 8],
        ['Grade 3', 4, 'Primary', 8, 9],
        ['Grade 4', 5, 'Primary', 9, 10],
        ['Grade 5', 6, 'Primary', 10, 11],
        ['Grade 6', 7, 'Primary', 11, 12],
        ['Grade 7', 8, 'Primary', 12, 13],
        ['Grade 8', 9, 'Primary', 13, 14],
        ['Form 1', 10, 'Secondary', 14, 15],
        ['Form 2', 11, 'Secondary', 15, 16],
        ['Form 3', 12, 'Secondary', 16, 17],
        ['Form 4', 13, 'Secondary', 17, 18],
    ];

    foreach ($grades as $i => $grade) {
        $pdo->exec("
            INSERT INTO grades (grade_name, grade_level, grade_category, min_age, max_age, sort_order)
            VALUES ('{$grade[0]}', {$grade[1]}, '{$grade[2]}', {$grade[3]}, {$grade[4]}, {$i})
            ON DUPLICATE KEY UPDATE grade_name = grade_name
        ");
    }
    echo "   ✓ {$pdo->lastInsertId()} grades seeded\n";

    // 2. Create Intake Campaign
    echo "2. Creating intake campaign...\n";
    $currentYear = date('Y');
    $stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $academicYearId = $stmt->fetchColumn();

    if ($academicYearId) {
        $pdo->exec("
            INSERT INTO intake_campaigns (campaign_name, academic_year_id, start_date, end_date, application_fee, status)
            VALUES ('2025 Intake', {$academicYearId}, '2025-01-01', '2025-12-31', 500.00, 'open')
            ON DUPLICATE KEY UPDATE campaign_name = campaign_name
        ");
        $intakeCampaignId = $pdo->lastInsertId();
        if (!$intakeCampaignId) {
            $stmt = $pdo->query("SELECT id FROM intake_campaigns WHERE campaign_name = '2025 Intake' LIMIT 1");
            $intakeCampaignId = $stmt->fetchColumn();
        }
        echo "   ✓ Intake campaign created (ID: {$intakeCampaignId})\n";
    }

    // 3. Seed Sample Applicants
    echo "3. Seeding sample applicants...\n";

    $sampleApplicants = [
        [
            'first_name' => 'John',
            'last_name' => 'Kamau',
            'dob' => '2015-03-15',
            'gender' => 'male',
            'grade' => 'Grade 1',
            'status' => 'submitted',
            'phone' => '0712345678',
            'email' => 'john.kamau@example.com',
            'guardian_name' => 'Peter Kamau',
            'guardian_phone' => '0723456789'
        ],
        [
            'first_name' => 'Mary',
            'last_name' => 'Wanjiku',
            'dob' => '2016-07-22',
            'gender' => 'female',
            'grade' => 'PP2',
            'status' => 'screening',
            'phone' => '0734567890',
            'email' => 'mary.wanjiku@example.com',
            'guardian_name' => 'Jane Wanjiku',
            'guardian_phone' => '0745678901'
        ],
        [
            'first_name' => 'David',
            'last_name' => 'Omondi',
            'dob' => '2014-11-10',
            'gender' => 'male',
            'grade' => 'Grade 2',
            'status' => 'interview_scheduled',
            'phone' => '0756789012',
            'email' => 'david.omondi@example.com',
            'guardian_name' => 'Sarah Omondi',
            'guardian_phone' => '0767890123'
        ],
        [
            'first_name' => 'Grace',
            'last_name' => 'Akinyi',
            'dob' => '2013-05-18',
            'gender' => 'female',
            'grade' => 'Grade 3',
            'status' => 'interviewed',
            'phone' => '0778901234',
            'email' => 'grace.akinyi@example.com',
            'guardian_name' => 'James Akinyi',
            'guardian_phone' => '0789012345'
        ],
        [
            'first_name' => 'Daniel',
            'last_name' => 'Mwangi',
            'dob' => '2012-09-03',
            'gender' => 'male',
            'grade' => 'Grade 4',
            'status' => 'accepted',
            'phone' => '0790123456',
            'email' => 'daniel.mwangi@example.com',
            'guardian_name' => 'Alice Mwangi',
            'guardian_phone' => '0701234567'
        ],
        [
            'first_name' => 'Faith',
            'last_name' => 'Njeri',
            'dob' => '2015-12-25',
            'gender' => 'female',
            'grade' => 'Grade 1',
            'status' => 'draft',
            'phone' => '0712345009',
            'email' => 'faith.njeri@example.com',
            'guardian_name' => 'Paul Njeri',
            'guardian_phone' => '0723450090'
        ],
    ];

    foreach ($sampleApplicants as $applicant) {
        // Get grade ID
        $stmt = $pdo->prepare("SELECT id FROM grades WHERE grade_name = :grade LIMIT 1");
        $stmt->execute(['grade' => $applicant['grade']]);
        $gradeId = $stmt->fetchColumn();

        // Generate application ref
        $appRef = 'APP' . date('Y') . strtoupper(substr(md5(rand()), 0, 6));

        // Insert applicant
        $stmt = $pdo->prepare("
            INSERT INTO applicants (
                application_ref, intake_campaign_id, academic_year_id, grade_applying_for_id,
                first_name, last_name, date_of_birth, gender, nationality, status,
                application_date, submitted_at, created_by
            ) VALUES (
                :app_ref, :intake_id, :year_id, :grade_id,
                :first_name, :last_name, :dob, :gender, 'Kenya', :status,
                CURDATE(), :submitted_at, 1
            )
        ");

        $submittedAt = in_array($applicant['status'], ['submitted', 'screening', 'interview_scheduled', 'interviewed', 'accepted'])
            ? date('Y-m-d H:i:s')
            : null;

        $stmt->execute([
            'app_ref' => $appRef,
            'intake_id' => $intakeCampaignId ?? null,
            'year_id' => $academicYearId,
            'grade_id' => $gradeId,
            'first_name' => $applicant['first_name'],
            'last_name' => $applicant['last_name'],
            'dob' => $applicant['dob'],
            'gender' => $applicant['gender'],
            'status' => $applicant['status'],
            'submitted_at' => $submittedAt
        ]);

        $applicantId = $pdo->lastInsertId();

        // Insert contact
        $stmt = $pdo->prepare("
            INSERT INTO applicant_contacts (applicant_id, phone, email, country, is_primary)
            VALUES (:applicant_id, :phone, :email, 'Kenya', 1)
        ");
        $stmt->execute([
            'applicant_id' => $applicantId,
            'phone' => $applicant['phone'],
            'email' => $applicant['email']
        ]);

        // Insert guardian
        $stmt = $pdo->prepare("
            INSERT INTO applicant_guardians (
                applicant_id, first_name, last_name, relationship, phone, is_primary
            ) VALUES (
                :applicant_id, :first_name, :last_name, 'Parent', :phone, 1
            )
        ");
        $nameParts = explode(' ', $applicant['guardian_name']);
        $stmt->execute([
            'applicant_id' => $applicantId,
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $applicant['guardian_phone']
        ]);

        echo "   ✓ {$applicant['first_name']} {$applicant['last_name']} ({$appRef}) - {$applicant['status']}\n";
    }

    echo "\n========================================\n";
    echo "✓ Sample data seeded successfully!\n";
    echo "========================================\n\n";

    echo "Summary:\n";
    echo "  - " . count($grades) . " grades created\n";
    echo "  - 1 intake campaign created\n";
    echo "  - " . count($sampleApplicants) . " sample applicants created\n";
    echo "  - Each with contacts and guardians\n\n";

    echo "Applicant Statuses:\n";
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM applicants
        GROUP BY status
        ORDER BY count DESC
    ");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['status']}: {$row['count']}\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
