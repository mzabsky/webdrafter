<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

class CollationTable
{
	protected $tableGateway;
	
	public function __construct(TableGateway $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}

	public function getCollation($id)
	{
		$id  = (int) $id;
		$rowset = $this->tableGateway->select(array('collation_id' => $id));
		$row = $rowset->current();
		if (!$row) {
			return null;
		}
		return $row;
	}
	
	public function fetchAll()
	{
		$resultSet = $this->tableGateway->select();
		return $resultSet;
	}
	
	public function fetchBySetVersion($setVersionId)
	{
		$resultSet = $this->tableGateway->select(array('set_version_id' => $setVersionId));
		return $resultSet;
	}

	public function saveCollation(Collation $collation)
	{
		$data = array(
			'collation_id' => $collation->collationId,
			'set_version_id' => $collation->setVersionId,
			'pack_number' => $collation->packNumber,
			'card_id' => $collation->cardId
		);
	
		$id = (int) $card->cardId;
		if ($id == 0) {
			$this->tableGateway->insert($data);
			$collation->collationId = $this->tableGateway->lastInsertValue;
		} else {
			if ($this->getCard($id)) {
				$this->tableGateway->update($data, array('card_id' => $id));
			} else {
				throw new \Exception('Card id does not exist');
			}
		}
	}


	public function saveCollations($packs, $setVersionId, $cardIdMap)
	{
			$data = array();
			$packNumber = 1;
	    foreach($packs as $pack)
	    {
				foreach($pack as $card)
				{
					$row = array(
						'collation_id' => null,
						'set_version_id' => $setVersionId,
						'pack_number' => $packNumber,
						'card_id' => $cardIdMap[$card]
					);
	
					$data[] = $row;
				}

				$packNumber++;
	    }

        $sqlStringTemplate = 'INSERT INTO %s (%s) VALUES %s';
        $adapter = $this->tableGateway->adapter; /* Get adapter from tableGateway */
        $driver = $adapter->getDriver();
        $platform = $adapter->getPlatform();

        $tableName = $platform->quoteIdentifier('collation');
        $parameterContainer = new \Zend\Db\Adapter\ParameterContainer();
        $statementContainer = $adapter->createStatement();
        $statementContainer->setParameterContainer($parameterContainer);

        /* Preparation insert data */
        $insertQuotedValue = [];
        $insertQuotedColumns = [];
        $i = 0;
        foreach ($data as $insertData) {
            $fieldName = 'field' . ++$i . '_';
            $oneValueData = [];
            $insertQuotedColumns = [];
            foreach ($insertData as $column => $value) {
                $oneValueData[] = $driver->formatParameterName($fieldName . $column);
                $insertQuotedColumns[] = $platform->quoteIdentifier($column);
                $parameterContainer->offsetSet($fieldName . $column, $value);
            }
            $insertQuotedValue[] = '(' . implode(',', $oneValueData) . ')';
        }

        /* Preparation sql query */
        $query = sprintf(
            $sqlStringTemplate,
            $tableName,
            implode(',', $insertQuotedColumns),
            implode(',', array_values($insertQuotedValue))
        );

        $statementContainer->setSql($query);
        return $statementContainer->execute();
	}

	/*public function delete($id)
	{
		$this->tableGateway->delete(array('id' => (int) $id));
	}*/
}
