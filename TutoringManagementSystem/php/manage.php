<?php

require_once 'db_connect.php';
require_once 'admin_check.php';
require_once 'app_helpers.php';

$definitions = [
    'students' => [
        'title' => 'Manage students',
        'table' => 'students',
        'id' => 'student_id',
        'fields' => [
            'first_name' => ['First name', 'text', 's'],
            'last_name' => ['Last name', 'text', 's'],
            'email' => ['Email', 'email', 's'],
            'phone' => ['Phone', 'tel', 's']
        ],
        'create_password' => true
    ],
    'tutors' => [
        'title' => 'Manage tutors',
        'table' => 'tutors',
        'id' => 'tutor_id',
        'fields' => [
            'first_name' => ['First name', 'text', 's'],
            'last_name' => ['Last name', 'text', 's'],
            'email' => ['Email', 'email', 's'],
            'phone' => ['Phone', 'tel', 's'],
            'expertise_subject' => ['Subject', 'text', 's'],
            'availability_status' => ['Status', 'availability', 's']
        ],
        'create_password' => true,
        'update_password' => true
    ],
    'programmes' => [
        'title' => 'Manage programmes',
        'table' => 'programmes',
        'id' => 'programme_id',
        'fields' => [
            'programme_name' => ['Programme name', 'text', 's'],
            'description' => ['Description', 'textarea', 's'],
            'fee_amount' => ['Fee amount (R)', 'number', 'd']
        ]
    ],
    'bookings' => [
        'title' => 'Manage bookings',
        'table' => 'bookings',
        'id' => 'booking_id',
        'fields' => [
            'student_id' => ['Student', 'student', 'i'],
            'tutor_id' => ['Tutor', 'tutor', 'i'],
            'programme_id' => ['Programme', 'programme', 'i'],
            'booking_date' => ['Date', 'date', 's'],
            'booking_time' => ['Time', 'time', 's'],
            'booking_status' => ['Status', 'booking_status', 's']
        ]
    ]
];

$type = $_GET['type'] ?? $_POST['type'] ?? '';
if (!isset($definitions[$type])) {
    http_response_code(404);
    renderMessagePage('Page not found', 'Choose a valid administration section.', [
        'Admin dashboard' => 'admin_dashboard.php'
    ]);
    exit();
}

$config = $definitions[$type];
$table = $config['table'];
$idColumn = $config['id'];
$fields = $config['fields'];
$message = '';
$transactionStarted = false;

function bindAndExecute(mysqli_stmt $statement, string $types, array $values)
{
    $bindings = [$types];
    foreach ($values as $key => &$value) {
        $bindings[] = &$value;
    }

    call_user_func_array([$statement, 'bind_param'], $bindings);
    return $statement->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            throw new InvalidArgumentException('This form expired. Reload the page and try again.');
        }

        $conn->begin_transaction();
        $transactionStarted = true;

        if ($action === 'delete' && $recordId) {
            $statement = $conn->prepare("DELETE FROM {$table} WHERE {$idColumn} = ?");
            bindAndExecute($statement, 'i', [$recordId]);
            $message = 'Record deleted.';
        } elseif (in_array($action, ['create', 'update'], true)) {
            $values = [];
            $types = '';

            foreach ($fields as $name => $field) {
                $value = trim($_POST[$name] ?? '');
                if ($value === '') {
                    throw new InvalidArgumentException($field[0] . ' is required.');
                }
                if ($field[1] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Enter a valid email address.');
                }
                if ($field[1] === 'number' && (
                    !is_numeric($value) ||
                    ($field[2] === 'i' && (int) $value < 1) ||
                    ($field[2] === 'd' && (float) $value < 0)
                )) {
                    throw new InvalidArgumentException($field[0] . ' must be a valid non-negative number.');
                }
                if (
                    in_array($field[1], ['student', 'tutor', 'programme'], true) &&
                    (!ctype_digit($value) || (int) $value < 1)
                ) {
                    throw new InvalidArgumentException('Choose a valid ' . strtolower($field[0]) . '.');
                }
                if ($field[1] === 'availability' && !in_array($value, ['Available', 'Unavailable'], true)) {
                    throw new InvalidArgumentException('Choose a valid tutor status.');
                }
                if ($field[1] === 'booking_status' && !in_array($value, ['Requested', 'Confirmed', 'Cancelled', 'Completed'], true)) {
                    throw new InvalidArgumentException('Choose a valid booking status.');
                }
                $values[] = $field[2] === 'd' ? (float) $value : ($field[2] === 'i' ? (int) $value : $value);
                $types .= $field[2];
            }

            if ($action === 'create' && !empty($config['create_password'])) {
                $plainPassword = $_POST['password'] ?? '';
                if (!is_string($plainPassword) || strlen($plainPassword) < 8) {
                    throw new InvalidArgumentException('Password must be at least 8 characters.');
                }
                $values[] = password_hash($plainPassword, PASSWORD_DEFAULT);
                $types .= 's';
            }

            if ($action === 'create') {
                $columns = array_keys($fields);
                $placeholders = array_fill(0, count($columns), '?');
                if (!empty($config['create_password'])) {
                    $columns[] = 'password';
                    $placeholders[] = '?';
                }
                if ($type === 'students') {
                    $columns[] = 'registration_date';
                    $placeholders[] = 'CURDATE()';
                }

                $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
                $statement = $conn->prepare($sql);
                bindAndExecute($statement, $types, $values);
                if ($type === 'bookings') {
                    addBookingNotifications(
                        $conn,
                        (int) $conn->insert_id,
                        'A tutoring session has been requested by an administrator.'
                    );
                }
                $message = 'Record created.';
            } elseif ($action === 'update' && $recordId) {
                $updateFields = array_keys($fields);
                if (!empty($config['update_password']) && ($_POST['password'] ?? '') !== '') {
                    $plainPassword = $_POST['password'];
                    if (!is_string($plainPassword) || strlen($plainPassword) < 8) {
                        throw new InvalidArgumentException('Password must be at least 8 characters.');
                    }
                    $updateFields[] = 'password';
                    $values[] = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $types .= 's';
                }
                $assignments = array_map(static function ($fieldName) {
                    return $fieldName . ' = ?';
                }, $updateFields);
                $values[] = $recordId;
                $types .= 'i';
                $statement = $conn->prepare(
                    "UPDATE {$table} SET " . implode(', ', $assignments) . " WHERE {$idColumn} = ?"
                );
                bindAndExecute($statement, $types, $values);
                if ($type === 'bookings') {
                    addBookingNotifications(
                        $conn,
                        (int) $recordId,
                        'Session details or status were updated by an administrator.'
                    );
                }
                $message = 'Record updated.';
            } else {
                throw new InvalidArgumentException('Choose a valid action.');
            }
        } else {
            throw new InvalidArgumentException('Choose a valid action.');
        }
        $conn->commit();
        $transactionStarted = false;
    } catch (InvalidArgumentException $exception) {
        if ($transactionStarted) {
            $conn->rollback();
            $transactionStarted = false;
        }
        $message = $exception->getMessage();
    } catch (mysqli_sql_exception | RuntimeException $exception) {
        if ($transactionStarted) {
            $conn->rollback();
            $transactionStarted = false;
        }
        error_log('Administration action failed: ' . $exception->getMessage());
        $message = $exception->getCode() === 1062
            ? 'A record with that email already exists.'
            : 'The record could not be saved. Check related IDs and try again.';
    }
}

$editRecord = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $conn->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ?");
    $statement->bind_param('i', $editId);
    $statement->execute();
    $editRecord = $statement->get_result()->fetch_assoc();
}

if ($type === 'bookings') {
    $records = $conn->query(
        "SELECT b.*,
                CONCAT(s.first_name, ' ', s.last_name, ' (', s.email, ')') AS student_display,
                CONCAT(t.first_name, ' ', t.last_name, ' (', t.email, ')') AS tutor_display,
                p.programme_name AS programme_display
         FROM bookings b
         JOIN students s ON s.student_id = b.student_id
         JOIN tutors t ON t.tutor_id = b.tutor_id
         JOIN programmes p ON p.programme_id = b.programme_id
         ORDER BY b.{$idColumn} DESC
         LIMIT 200"
    );
} else {
    $records = $conn->query("SELECT * FROM {$table} ORDER BY {$idColumn} DESC LIMIT 200");
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo escapeHtml($config['title']); ?></title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <main class="dashboard">
    <h1><?php echo escapeHtml($config['title']); ?></h1>
    <?php if ($message !== ''): ?>
      <p class="status-message" role="status"><?php echo escapeHtml($message); ?></p>
    <?php endif; ?>

    <section class="card">
      <h2><?php echo $editRecord ? 'Edit record' : 'Add record'; ?></h2>
      <form method="POST" action="manage.php?type=<?php echo escapeHtml($type); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
        <input type="hidden" name="type" value="<?php echo escapeHtml($type); ?>">
        <input type="hidden" name="action" value="<?php echo $editRecord ? 'update' : 'create'; ?>">
        <?php if ($editRecord): ?>
          <input type="hidden" name="record_id" value="<?php echo (int) $editRecord[$idColumn]; ?>">
        <?php endif; ?>
        <?php foreach ($fields as $name => $field): ?>
          <div>
            <label for="<?php echo escapeHtml($name); ?>"><?php echo escapeHtml($field[0]); ?></label>
            <?php if (in_array($field[1], ['student', 'tutor', 'programme'], true)): ?>
              <?php
              $lookups = [
                  'student' => 'SELECT student_id, CONCAT(first_name, " ", last_name) AS display_name FROM students ORDER BY first_name, last_name',
                  'tutor' => 'SELECT tutor_id, CONCAT(first_name, " ", last_name) AS display_name FROM tutors ORDER BY first_name, last_name',
                  'programme' => 'SELECT programme_id, programme_name AS display_name FROM programmes ORDER BY programme_name'
              ];
              $idNames = ['student' => 'student_id', 'tutor' => 'tutor_id', 'programme' => 'programme_id'];
              $options = $conn->query($lookups[$field[1]]);
              ?>
              <select id="<?php echo escapeHtml($name); ?>" name="<?php echo escapeHtml($name); ?>" required>
                <option value="">Select <?php echo escapeHtml(strtolower($field[0])); ?></option>
                <?php while ($option = $options->fetch_assoc()): ?>
                  <option value="<?php echo (int) $option[$idNames[$field[1]]]; ?>" <?php echo ((int) ($editRecord[$name] ?? 0) === (int) $option[$idNames[$field[1]]]) ? 'selected' : ''; ?>><?php echo escapeHtml($option['display_name']); ?></option>
                <?php endwhile; ?>
              </select>
            <?php elseif ($field[1] === 'availability' || $field[1] === 'booking_status'): ?>
              <select id="<?php echo escapeHtml($name); ?>" name="<?php echo escapeHtml($name); ?>" required>
                <?php
                $options = $field[1] === 'availability'
                    ? ['Available', 'Unavailable']
                    : ['Requested', 'Confirmed', 'Cancelled', 'Completed'];
                foreach ($options as $option):
                ?>
                  <option value="<?php echo escapeHtml($option); ?>" <?php echo (($editRecord[$name] ?? '') === $option) ? 'selected' : ''; ?>><?php echo escapeHtml($option); ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($field[1] === 'textarea'): ?>
              <textarea id="<?php echo escapeHtml($name); ?>" name="<?php echo escapeHtml($name); ?>" required><?php echo escapeHtml($editRecord[$name] ?? ''); ?></textarea>
            <?php else: ?>
              <input id="<?php echo escapeHtml($name); ?>" type="<?php echo escapeHtml($field[1]); ?>" name="<?php echo escapeHtml($name); ?>" value="<?php echo escapeHtml($editRecord[$name] ?? ''); ?>" <?php echo $field[1] === 'number' ? 'min="0" step="' . ($field[2] === 'd' ? '0.01' : '1') . '"' : ''; ?> required>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if ($type === 'students' && !$editRecord): ?>
          <div>
            <label for="password">Temporary password</label>
            <input id="password" type="password" name="password" minlength="8" required>
          </div>
        <?php endif; ?>
        <?php if ($type === 'tutors' && !$editRecord): ?>
          <div>
            <label for="password">Temporary password for tutor account</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>
          </div>
        <?php endif; ?>
        <?php if ($type === 'tutors' && $editRecord): ?>
          <div>
            <label for="password">Set or reset tutor password (leave blank to keep current)</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password">
          </div>
        <?php endif; ?>
        <button type="submit"><?php echo $editRecord ? 'Save changes' : 'Add record'; ?></button>
        <?php if ($editRecord): ?>
          <p class="page-link"><a href="manage.php?type=<?php echo escapeHtml($type); ?>">Cancel edit</a></p>
        <?php endif; ?>
      </form>
    </section>

    <section class="card table-card">
      <h2>Records</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><th>ID</th><?php foreach ($fields as $field): ?><th><?php echo escapeHtml($field[0]); ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
          <tbody>
          <?php while ($record = $records->fetch_assoc()): ?>
            <tr>
              <td><?php echo (int) $record[$idColumn]; ?></td>
              <?php foreach ($fields as $name => $field): ?>
                <?php
                $displayValue = $record[$name];
                if ($type === 'bookings') {
                    $displayFields = [
                        'student_id' => 'student_display',
                        'tutor_id' => 'tutor_display',
                        'programme_id' => 'programme_display'
                    ];
                    $displayValue = $record[$displayFields[$name] ?? $name];
                }
                ?>
                <td><?php echo escapeHtml($name === 'password' ? '' : $displayValue); ?></td>
              <?php endforeach; ?>
              <td class="table-actions">
                <a href="manage.php?type=<?php echo escapeHtml($type); ?>&amp;edit=<?php echo (int) $record[$idColumn]; ?>">Edit</a>
                <form method="POST" action="manage.php?type=<?php echo escapeHtml($type); ?>" onsubmit="return confirm('Delete this record?');">
                  <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(getCsrfToken()); ?>">
                  <input type="hidden" name="type" value="<?php echo escapeHtml($type); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="record_id" value="<?php echo (int) $record[$idColumn]; ?>">
                  <button class="button-small" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
    <p class="page-link"><a href="admin_dashboard.php">Back to admin dashboard</a></p>
  </main>
</body>
</html>
