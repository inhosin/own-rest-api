<?
require_once('mysql.worker.php');
require_once('apiConstants.php');

/**
 *
 */
class APIEngine
{
  private $_apiFunctionName;
  private $_apiFunctionParams;

  // статическая функция для подключения API из других API при необходимости, в методах
  static function getAPIEngineByName($apiName)
  {
    require_once 'apiBase.class.php';
    require_once $apiName . '.php';
    $apiClass = new $apiName();
    return $apiClass;
  }

  function __construct($apiFunctionName, $apiFunctionParams)
  {
    $this->_apiFunctionParams = stripslashes($apiFunctionParams);
    $this->_apiFunctionName = explode('_', $apiFunctionName);
  }

  // создаём JSON ответ
  function createDefaultJson()
  {
    $retObject = json_decode('{}');
    $response = APIConstants::$RESPONSE;
    $retObject->$response = json_decode('{}');
    return $retObject;
  }

  // вызов функциии по переданным данным в конструкторе
  function callApiFunction()
  {
    $resultFunctionCall = $this->createDefaultJson(); // создаём ответ
    $apiName = strtolower($this->apiFunctionName[0]); // приводим в нижний регистрназвание
    if (file_exists($apiName . '.php')) {
      $apiClass = APIEngine::getAPIEngineByName($apiName);
      $apiReflection = new ReflectionClass($apiName); // через рефлексию получаем информацию о классе объекта
      try {
        $functionName $this->_apiFunctionName[1];
        $apiReflection->getMethod($functionName);
        $response = APIConstants::$RESPONSE;
        $jsonParam = json_decode($this->_apiFunctionParams);
        if ($jsonParam) {
          if (isset($jsonParam->responseBinary)) {
            return $apiClass->$functionName($jsonParam);
          } else {
            $resultFunctionCall->$response = $apiClass->$functionName($jsonParam);
          }
        } else {
          $resultFunctionCall->errno = APIConstants::$ERROR_ENGINE_PARAMS;
          $resultFunctionCall->error = 'Error given params';
        }
      } catch (Exception $e) {
        $resultFunctionCall->error = $e->getMessage();
      }
    } else {
      //  если API не найден
      $resultFunctionCall->errno = APIConstants::$ERROR_ENGINE_PARAMS;
      $resultFunctionCall->error = 'File not found';
      $resultFunctionCall->REQUEST = $_REQUEST;
    }
    return json_decode($resultFunctionCall);
  }
}

?>
