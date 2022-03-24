<?php
/*
 * This function redirects the user to a page.
 */
function redirect($path)
{
    header("Location: /{$path}");
}

/*
 * This function returns the view of a page.
 */
function view($name, $data = [])
{
    extract($data);
    return require "../app/views/{$name}.view.php";
}
/*
 * This function is used for dark mode functionality,
 * it returns the first (dark) class string
 * or second (light class string).
 */
function theme($class, $secondClass) {
    if (isset($_SESSION['darkmode']) && $_SESSION['darkmode'] == true) {
        return $class;
    }
    return $secondClass;
}
/*
 * This function is used for dying and dumping.
 */
function dd($value)
{
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}

/*
 * This function is used for generating pagination links.
 */
function paginate($table, $page, $limit, $count)
{
    $offset = ($page - 1) * $limit;
    $output = "<span class='". theme('text-white-75', 'text-dark')  ."'>";
    if ($page > 1) {
        $prev = $page - 1;
        $output .= "<a href='/{$table}/{$prev}' class='".  theme('text-light', 'text-primary') ."'>Prev</a>";
    }
    $output .= " Page $page ";
    if ($count > ($offset + $limit)) {
        $next = $page + 1;
        $output .= "<a href='/{$table}/{$next}' class='".  theme('text-light', 'text-primary')  ."'>Next</a>";
    }
    $output .= "</span>";
    return $output;
}

/*
 * This function displays a session variable's value if it exists.
*/
function session($name) {
    return $_SESSION[$name] ?? "";
}

/*
 * This function displays a session variable's value and unsets it if it exists.
 */
function session_once($name) {
    if (isset($_SESSION[$name])) {
        $value = $_SESSION[$name];
        unset($_SESSION[$name]);
        return $value;
    }
    return "";
}

/*
 * This function enables displaying of errors in the web browser.
 */
function display_errors()
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

/*
 * This makes a curl getApiRequest.
 */
function getApiRequest($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    return $result;
}

/**
 * create random decimal
 */
function frand($min, $max, $decimals = 0): float
{
    $scale = pow(10, $decimals);
    $response = mt_rand($min * $scale, $max * $scale) / $scale;
    return $response == 0.0 ? 0.1 : $response;
}

/**
 * compare floats
 */
function compareFloats(float $var1, $operator, float $var2): float
{
    (int)$int1 = $var1 * 10;
    (int)$int2 = $var2 * 10;

    if ($operator == '==') {
        return $int1 == $int2;
    } elseif ($operator == '<=') {
        return $int1 <= $int2;
    } elseif ($operator == '>=') {
        return $int1 >= $int2;
    } elseif ($operator == '>') {
        return $int1 > $int2;
    } elseif ($operator == '<') {
        return $int1 < $int2;
    }
}

?>
