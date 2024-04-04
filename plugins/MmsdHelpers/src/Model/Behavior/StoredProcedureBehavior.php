<?php
namespace MmsdHelpers\Model\Behavior;
use Cake\Database\StatementInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Log\Log;

class StoredProcedureBehavior extends Behavior
{
    public function __construct(Table $table, array $config = [])
    {
        $config += [
            'resultField' => 'result',
            'msgField' => 'msg',
            'resultValueSuccess' => 'success',
            'resultValueFailure' => 'error',
        ];
        parent::__construct($table, $config);
    }
    /**
     * Executes a stored procedure with the given name and parameters.
     *
     * @param string $procedureName The name of the stored procedure to execute.
     * @param array $parameters An optional array of parameters to pass to the stored procedure.
     * Each parameter should be an associative array with a key 'value' representing the value of the parameter,
     * and an optional key 'type' representing the data type of the parameter.
     * @return StatementInterface The executed statement.
     */
    public function executeProcedure(string $procedureName, array $parameters = []): StatementInterface
    {
        $dbConnection = $this->table()->getConnection();
        $parameterString = '';
        $assignedValues = [];
        $parameterTypes = [];
        if (!empty($parameters)) {
            $parameterList = [];
            foreach ($parameters as $name => $data) {
                $parameterList[] = "@{$name}=:{$name}";
                if (is_array($data)) {
                    // if you send an array, one key must be 'value'
                    $assignedValues[$name] = $data['value'];
                    // type is still optional but then why are you sending an array lol
                    if (!empty($data['type'])) {
                        $parameterTypes[$name] = $data['type'];
                    }
                } else {
                    $assignedValues[$name] = $data;
                }
            }
            $parameterString = implode(', ',$parameterList);
        }
        return $dbConnection->execute(
            "{$procedureName} {$parameterString}"
            ,$assignedValues
            ,$parameterTypes
        );
    }
    /**
     * Runs a stored procedure with the given name and parameters.
     *
     * @param string $procedureName The name of the stored procedure to run.
     * @param array $parameters An optional array of parameters to pass to the stored procedure.
     * @return array The result of the stored procedure execution.
     */
    public function runStoredProcedure(string $procedureName, array $parameters = []): array
    {
        $result = [
            'success' => true,
            'error' => null,
        ];
        $procedureResult = $this->executeProcedure($procedureName,$parameters)->fetch('assoc');
        Log::debug(print_r($procedureResult,true));
        Log::debug(print_r($this->getConfig('resultField'),true));
        Log::debug(print_r($procedureResult[$this->getConfig('resultField')],true));
        Log::debug(print_r(empty($procedureResult[$this->getConfig('resultField')]),true));
        if (
            (empty($procedureResult[$this->getConfig('resultField')]))
            or ($procedureResult[$this->getConfig('resultField')] != $this->getConfig('resultValueSuccess'))
        ) {
            $result['success'] = false;
            $result['error'] = $procedureResult[$this->getConfig('msgField')] ?? null;
        }
        return $result;
    }
}
