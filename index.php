<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$programmes = [];
$selectedProgramme = null;
$students = [];
$error = null;

$programmeId = filter_input(
    INPUT_GET,
    'programme_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) ?: null;

try {
    $pdo = getConnection();

    $programmes = $pdo->query(
        'SELECT programme_id, title FROM programmes ORDER BY title'
    )->fetchAll();

    if ($programmeId !== null) {
        $stmt = $pdo->prepare(
            'SELECT programme_id, title FROM programmes WHERE programme_id = :id'
        );
        $stmt->execute(['id' => $programmeId]);
        $selectedProgramme = $stmt->fetch() ?: null;

        if ($selectedProgramme !== null) {
            $stmt = $pdo->prepare(
                'SELECT student_id, first_name, last_name,
                        final_classification, manual_override
                FROM progr_students
                WHERE programme_id = :id
                ORDER BY last_name, first_name'
            );
            $stmt->execute(['id' => $programmeId]);
            $students = $stmt->fetchAll();
        }
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $error = 'Sorry, the student data could not be loaded';
}
?>

<!DOCTYPE html>
<html lang="en-GB">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HEdClass - Programme Classifications</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            margin: 2rem auto;
            max-width: 52rem;
            padding: 0 1rem;
            color: #1a1a1a;
        }

        h1 {
            font-size: 1.5rem
        }

        form {
            display: flex;
            gap: .5rem;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        select,
        button {
            font: inherit;
            padding: .4rem .6rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        caption {
            text-align: left;
            font-weight: 600;
            margin-bottom: .5rem
        }

        th,
        td {
            text-align: left;
            padding: .5rem;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f3f3f3;
        }

        .error {
            color: #a00;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <h1>Programme classification</h1>
    <p>Read-only view of students and their final classification from the HEdClass database.</p>

    <?php if ($error !== null): ?>
        <p class="error" role="alert"><?= e($error) ?></p>
    <?php else: ?>

        <form method="get">
            <div>
                <label for="programme_id">Programme</label>
                <select id="programme_id" name="programme_id">
                    <option value="">Choose a programme</option>
                    <?php foreach ($programmes as $programme): ?>
                        <option value="<?= (int) $programme['programme_id'] ?>"
                            <?= (int) $programme['programme_id'] === $programmeId ? 'selected' : '' ?>>
                            <?= e($programme['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Show students</button>
        </form>


        <?php if ($programmeId !== null && $selectedProgramme === null): ?>
            <p>No programme found for that selection.</p>
        <?php elseif ($selectedProgramme !== null): ?>
            <?php if (count($students) === 0): ?>
                <p>No students are enrolled on <?= e($selectedProgramme['title']) ?>.</p>
            <?php else: ?>
                <table>
                    <caption>
                        <?= e($selectedProgramme['title']) ?> (<?= count($students) ?> students)
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Student ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Final classification</th>
                            <th scope="col">Manual override</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= (int) $student['student_id'] ?></td>
                                <td><?= e($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                <td><?= e($student['final_classification'] ?? 'Not yet classified') ?></td>
                                <td><?= (int) $student['manual_override'] === 1 ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

    <?php endif; ?>
</body>

</html>