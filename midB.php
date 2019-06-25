<?php
//Error checking
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Check for code injection
define("BAD_CODE", "BAD CODE: Code should not use import or eval.");

if(isset($_POST['json_string'])){
    $postData = $_POST['json_string'];
    $postData = json_decode($postData, true);
    $purpose = $postData["purpose"];

    if($purpose == "submit_exam"){
        $exam = $postData['data'];
        $examResults = array(
            "username"=>$exam['username'],
            "Exam ID" => $exam['Exam ID'],
            "Student Answer" => $exam['Student Answer'],
            "Question Point Value" => $exam['Question Point Value'], 
            "Score" => '',
            "Results" => array()
        );
        $earnedPoints = 0; $totalPoints = 0;
        
        $pointsEarned = array(  'functionNamePoints' => 5,
                                'deliveryPoints' => 3, 
                                'constraintsPoints' => 3,
                                'runPoints' => 0 
        );
        
        //Grade each question and store it in $examResults, e.g.$examResults[$i] is the results for question number i.
        for($i = 0; $i < count($exam['Question Grading Info']); $i++){
            /*Determine how much each test case is worth by subtracting the static number of points from func name, delivery method, 
            and proper usage of constraints, then eventually divded by number of test cases,
            e.g. a 50 point question with 3 test cases, each test case is worth 13 points.*/
            $pointsEarned['runPoints'] = intval($exam['Question Point Value'][$i]) - ($pointsEarned['functionNamePoints'] + $pointsEarned['deliveryPoints'] + $pointsEarned['constraintsPoints']);

            //Grade the student's code
            $results = run($exam['Question Grading Info'][$i], $exam["Student Answer"][$i], $exam["Question Point Value"][$i], $pointsEarned);

            //Calculate the score
            $earnedPoints += $results['earnedPoints'];
            $totalPoints += $exam['Question Point Value'][$i];
            $results = json_encode($results);
            $examResults['Results'][$i] = $results;
        }

    //Pack up the graded exam, send it to the back
    $examResults['Score'] = $earnedPoints/$totalPoints;
    $data = array(  "purpose" => "submit_exam", 
                    "data" => $examResults
    );
    $data = json_encode($data);
    $data = array("json_string" => $data);
    curlToBack($data);
    
    } 
    
    //Otherwise, send it to back and let them figure it out the purpose
    else {
        $postData = json_encode($postData);
        $postData = array("json_string" => $postData);
        curlToBack($postData);
    }
}

//Curl a JSON to the backend
function curlToBack($postData){
    $urlBackEnd = "https://web.njit.edu/~rv272/newTest.php";
    $ch = curl_init();
    $optionsBackEnd = array(
        CURLOPT_URL => $urlBackEnd,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => TRUE,
    );
    curl_setopt_array($ch, $optionsBackEnd);
    $dataFromBack = curl_exec($ch);
    echo $dataFromBack;
    curl_close($ch);
}

//Grade questions
function run($question, $studentProgram, $points, $pointsEarned){
    //Trim student code of white space
    $studentProgram = trim($studentProgram);
    $studentFuncName = trim(getStudentFunctionName($studentProgram));
    
    $resultString = "";
    $testCases = getTestCases($question['Test Cases']);
    $expectedResults = array_values($question['Test Cases']);
    
    $numArgNames = count($testCases[0]);
    $testResults = array();

    //Set the array to be returned
    $studentResults = array('earnedPoints' => 0,
                            'constraints' => array(),              
                            'function_name' => array(),        
                            'delivery_method' => array(),      
                            'Points' => $points,               
                            'test_cases' => array(),           
                            'test_cases_input' => array(),     
                            'test_cases_expected' => array(),  
                            'test_cases_output' => array(),    
    );   

    //Check for code injection in the function name
    if($studentFuncName === BAD_CODE) {
        echo BAD_CODE;
        return;
    }

    //Check if constraints followed successfully
    if(!empty($question['Constraints'])) {
        $const_passed = true;
        
        $c_failed = '';
        for($i = 0; $i < count($question['Constraints']); $i++){
            var_dump($question['Constraints'][$i]);
            var_dump($studentProgram); 

            echo "s:" . strpos($studentProgram, $question['Constraints'][$i]);

            if(preg_match('/(' . $question['Constraints'][$i] . ')/', $studentProgram) <= 0){
                echo "DID NOT FIND: " . $question['Constraints'][$i] . "<br/>";
                $const_passed = false;
                $c_failed = $question['Constraints'][$i];
            }
        }

        if($const_passed){
            echo "passed <br/>";
            $studentResults['constraints'] = array('status' => 'passed', 'points' => $pointsEarned['constraintsPoints'], 'totalPoints' => $pointsEarned['constraintsPoints']);
        }
        
        else {
            echo "failed <br/>";
            $studentResults['constraints'] = array('failedName' => $c_failed, 'status' => 'failed', 'points' => 0, 'totalPoints' => $pointsEarned['constraintsPoints']);
        }
        $studentResults['earnedPoints'] += $studentResults['constraints']['points'];
    }

    else {
        $pointsEarned['runPoints']+=$pointsEarned['constraintsPoints'];
    }
    
    //Handle the test cases.
    for ($i=0; $i < count($testCases); $i++) { 
      $testResults[$i] = runTest($studentFuncName, $testCases[$i], $studentProgram);
    }

    $testCaseWeight = floor($pointsEarned['runPoints'] / count($testResults));
    $remainder = ($pointsEarned['runPoints'] / count($testResults)) - $testCaseWeight;
    $pointsEarned['deliveryPoints'] += round($remainder * count($testResults));
    
    for ($i=0; $i < count($testResults); $i++) {
        $exp = $expectedResults[$i];
        $out = outputToString($testResults[$i]);

        if(($temp = strstr($out, "None", true)) !== false) {
            $out = $temp;
        }
          
        $studentResults['test_cases_input'][$i] = $testResults[$i]['func_call'];
        $studentResults['test_cases_expected'][$i] = $exp;
        $studentResults['test_cases_output'][$i] = $out;
        if($exp === $out){
            $studentResults['test_cases'][$i] = array('status' => 'passed', 'points' => $testCaseWeight, 'totalPoints' => $testCaseWeight);
        }
        else{
            $studentResults['test_cases'][$i] = array('status' => 'failed', 'points' => 0, 'totalPoints' => $testCaseWeight);
        }
        $studentResults['earnedPoints'] += $studentResults['test_cases'][$i]['points'];


    }

    //Check if delivery method followed successfully
    if($question['Delivery Method'] == "return" && preg_match('/( return[^a-zA-Z]?)/', $studentProgram) > 0){
        $studentResults['delivery_method'] = array('status' => 'passed', 'points' => $pointsEarned['deliveryPoints'], 'totalPoints' => $pointsEarned['deliveryPoints']);
    }
    else if($question['Delivery Method'] == "print" && preg_match('/( print[^a-zA-Z]?)/', $studentProgram) > 0){
        $studentResults['delivery_method'] = array('status' => 'passed', 'points' => $pointsEarned['deliveryPoints'], 'totalPoints' => $pointsEarned['deliveryPoints']);
    }
    else{
        $studentResults['delivery_method'] = array('status' => 'failed', 'points' => 0, 'totalPoints' => $pointsEarned['deliveryPoints']);
    }
    $studentResults['earnedPoints'] += $studentResults['delivery_method']['points'];
    //End of delivery check

    //Check if function name is correct
    if($question['Function Name'] != $studentFuncName){
        $studentResults['function_name'] = array('status' => 'failed', 'points' => 0, 'totalPoints' => $pointsEarned['functionNamePoints']);
    }
    else{
        $studentResults['function_name'] = array('status' => 'passed', 'points' => $pointsEarned['functionNamePoints'], 'totalPoints' => $pointsEarned['functionNamePoints']);
    }
    $studentResults['earnedPoints'] += $studentResults['function_name']['points'];
    //End of func name check
    
    return $studentResults;
} //End of run/grading

/*  Create 2D array to store testcases, followed by their arguments, 
    e.g. array[0][1] is the first test cases second argument */
function getTestCases($testCases){
  $arguments = array();
  foreach ($testCases as $key => $value) {
    array_push($arguments, preg_split("/[\s,]+/", $key));
  }
  return $arguments;
}

//Run a testcase through a student's code
function runTest($functionName, $testCase, $studentProgram){
    $python = "python";
    $pythonFile = fopen("tempPython.py", "w");
    $functionCall = $functionName . "(";
    for ($i=0; $i < count($testCase); $i++) { 
      $functionCall .= $testCase[$i];
      if($i < (count($testCase) - 1) ) {
        $functionCall .= ", ";
      }else{
        $functionCall .= ")";
      }
    }

    $progToRun = $studentProgram . "\nprint(" . $functionCall . ")";
    fwrite($pythonFile, $progToRun);
    fclose($pythonFile);
    
    exec("$python tempPython.py 2>&1", $stdout, $return_var);
    return array('output' => $stdout, 'return_var' => $return_var, 'func_call' => $functionCall);
}

//Turn student's code into an array, each index being a line of code. Delimit by space newlines
function getProgramLineByLine($program){
    $lines = array();
        $buffer = "";
        for ($i = 0; $i < strlen($program); $i++) {
            if($program[$i] != "\n")
                $buffer .= $program[$i];
            else{
                array_push($lines, $buffer);
                $buffer = "";
            }
        }
        array_push($lines, $buffer);
        return $lines;
    }

//Get the student's function anme
function getStudentFunctionName($studentProgram){
    $lines = getProgramLineByLine($studentProgram);
    foreach ($lines as $line) {
        //Checks for code injection, rejection is done above.
        if(strpos($line, "import") !== FALSE || strpos($line, "eval") !== FALSE){
            return BAD_CODE;
        }
    }
    $buffer = substr($lines[0], 3);
    return strstr($buffer, "(", true);
}

//Get student's arguments names, and how many there are.
function getStudentArgNames($studentProgram){
    $lines = getProgramLineByLine($studentProgram); //Create array of every line in the code. $lines[0] is the function name
    $argString = strstr($lines[0], "(");
    $rightParenPosition = strpos($argString, ")");

    $argString = substr($argString, 1, $rightParenPosition - 1);
    $argsArr = preg_split("/[\s,]+/", $argString);
    return count($argsArr); // return the number of arguments 
}

//Function converts the param $results to a string
function outputToString($results){
    $str = "";
    foreach ($results['output'] as $line) {
        if($results['return_var'] !== 0) {
            $str .= str_replace("File \"tempPython.py\","," ", $line);
        }
        else {
            $str .= $line;
        }
         
    }
    return $str;
}
?>