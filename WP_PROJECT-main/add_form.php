<?php
require 'header.php';
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Table & Import Excel To MySQL</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-5">
    <div class="max-w-md mx-auto bg-blue-900 p-5 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4 text-white">Create Table & Import Excel</h1>
        <form action="" enctype="multipart/form-data" method="post">
            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Department:</label>
                <select name="department" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                    <option value="CSE">CSE</option>
                    <option value="IT">IT</option>
                    <option value="ECE">ECE</option>
                    <option value="ME">ME</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Semester:</label>
                <select name="semester" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Course Name:</label>
                <input type="text" name="course_name" required placeholder="Enter course name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Course Code:</label>
                <input type="text" name="course_code" required placeholder="Enter course code" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Term Test Number:</label>
                <select name="term_test_number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
                    <option value="1">Test 1</option>
                    <option value="2">Test 2</option>
                    <option value="3">Test 3</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Number of Questions (Q1, Q2, ...):</label>
                <input type="number" name="subject_count" required min="1" placeholder="Enter number of questions" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Number of Course Outcome Columns:</label>
                <input type="number" name="course_outcome_count" required min="1" placeholder="Enter number of Course Outcome columns" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-white">Upload Excel File:</label>
                <input type="file" name="excel" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm">
            </div>

            <button type="submit" name="create_table" class="bg-blue-500 text-white py-2 px-4 rounded">Create Table & Import</button>
        </form>

        <?php
        if(isset($_POST['create_table'])){
            // Get form data to construct the table name
            $department = mysqli_real_escape_string($conn, $_POST['department']);
            $semester = mysqli_real_escape_string($conn, $_POST['semester']);
            $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
            $course_code = mysqli_real_escape_string($conn, $_POST['course_code']);
            $term_test_number = mysqli_real_escape_string($conn, $_POST['term_test_number']);

            // Generate the table name dynamically
            $table_name = $department . "_" . $semester . "_" . $course_name . "_" . $course_code . "_Test" . $term_test_number;

            // Get the subject count and course outcome count
            $subject_count = (int) $_POST['subject_count'];
            $course_outcome_count = (int) $_POST['course_outcome_count'];

            // Start creating the table with static columns
            $createTableQuery = "CREATE TABLE $table_name (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                `Div` VARCHAR(10),
                RollNo VARCHAR(30),
                SAPID VARCHAR(30),
                Name VARCHAR(30)";

            // Add subject columns dynamically based on the subject count
            for($i = 1; $i <= $subject_count; $i++) {
                $createTableQuery .= ", Q$i INT(3)";
            }

            // Add the TotalMarks column
            $createTableQuery .= ", TotalMarks INT(3)";

            // Add CourseOutcomeNumber columns dynamically
            for($j = 1; $j <= $course_outcome_count; $j++) {
                $createTableQuery .= ", CO$j INT(3)";
            }

            $createTableQuery .= ")";  // Close the table creation query

            // Execute the query to create the table
            if(mysqli_query($conn, $createTableQuery)){
                echo "Table '$table_name' created successfully with $subject_count question columns, TotalMarks, and $course_outcome_count Course Outcome columns!<br>";

                // Proceed with Excel upload and import
                if(isset($_FILES['excel'])){
                    $fileName = $_FILES["excel"]["name"];
                    $fileExtension = explode('.', $fileName);
                    $fileExtension = strtolower(end($fileExtension));

                    $newFileName = date("Y.m.d") . " - " . date("h.i.sa") . "." . $fileExtension;

                    $targetDirectory = "uploads/" . $newFileName;
                    move_uploaded_file($_FILES["excel"]["tmp_name"], $targetDirectory);

                    // Disable error reporting for deprecated features
                    error_reporting(0);
                    ini_set('display_errors', 0);

                    require "excelReader/excel_reader2.php";
                    require "excelReader/SpreadsheetReader.php";

                    $reader = new SpreadsheetReader($targetDirectory);

                    // Insert data into the newly created table
                    foreach ($reader as $key => $row) {
                        // Ensure that we are reading from the correct Excel columns
                        $Div = mysqli_real_escape_string($conn, $row[0]);  // 1st column
                        $RollNo = mysqli_real_escape_string($conn, $row[1]);  // 2nd column
                        $SAPID = mysqli_real_escape_string($conn, $row[2]);  // 3rd column
                        $Name = mysqli_real_escape_string($conn, $row[3]);  // 4th column
                        
                        // Prepare subjects dynamically based on the subject count
                        $subjects = [];
                        for($i = 1; $i <= $subject_count; $i++) {
                            // Read data for each subject column from Excel
                            $subjects[] = isset($row[3 + $i]) ? (int)$row[3 + $i] : 'NULL';  // Starting from the 5th column
                        }

                        // Calculate TotalMarks as the sum of the subjects
                        $TotalMarks = array_sum($subjects);

                        // Prepare Course Outcome columns dynamically based on the course outcome count
                        $course_outcomes = [];
                        for($j = 1; $j <= $course_outcome_count; $j++) {
                            // Read data for each Course Outcome column from Excel
                            $course_outcomes[] = isset($row[4 + $subject_count + $j]) ? (int)$row[4 + $subject_count + $j] : 'NULL';  // After subjects
                        }

                        // Insert data into the table
                        $insertQuery = "INSERT INTO $table_name (`Div`, RollNo, SAPID, Name, TotalMarks, " .
                            implode(", ", array_map(fn($i) => "Q$i", range(1, $subject_count))) . ", " .
                            implode(", ", array_map(fn($j) => "CO$j", range(1, $course_outcome_count))) . ") VALUES ('$Div', '$RollNo', '$SAPID', '$Name', $TotalMarks, " .
                            implode(", ", $subjects) . ", " .
                            implode(", ", $course_outcomes) . ")";

                        mysqli_query($conn, $insertQuery);
                    }

                    echo "Data imported successfully from Excel!";
                }
            } else {
                echo "Error creating table: " . mysqli_error($conn);
            }
        }
        ?>
    </div>
</body>
</html>
