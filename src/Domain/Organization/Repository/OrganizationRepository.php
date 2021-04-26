<?php

namespace App\Domain\Organization\Repository;

use PDO;


class OrganizationRepository
{
  
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }
	private function getNowSearchParams() 
	{
		$dayOfWeek = date('w');
		$dayOfWeek = $dayOfWeek ? $dayOfWeek : 7;
		
		return [
			':time'        => date('H:i'), 
			':day_of_week' => 1,
		];
	}
    public function fetchOpened(): array 
    { 
		$searchParams = $this->getNowSearchParams();
		
        $sql = "
			SELECT 
				o.name AS name,
				TIME_FORMAT(SUBTIME(s.`close`, :time), '%H:%i') AS closes_in
			FROM schedule AS s
			LEFT JOIN organization AS o ON o.id = s.organization_id	
			WHERE 
				s.day_of_week= :day_of_week AND
				s.`open`  <= :time AND
				s.`close` > :time
			ORDER BY 
				name;
		";
		
		$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); 
		$query = $this->connection->prepare($sql);
		$res = $query->execute($searchParams);
        //print_r($res);
		return $query->fetchAll(PDO::FETCH_ASSOC);
   }
   public function fetchClosed(): array 
   {
		$searchParams = $this->getNowSearchParams();
		$searchParams[':time'] = "22:00"; 
		$searchParams[':day_of_week'] = "7"; 
		// Составим список всех закрытых на даный момент организаций
        $sqlClosedOrganizations = "
			SELECT 
				id,
				name
			FROM  organization 
			WHERE 
				id NOT IN(
					SELECT 
						organization_id
					FROM schedule AS s
					WHERE 
						s.day_of_week= :day_of_week AND
						s.`open`  <= :time AND
						s.`close` > :time
				)
			ORDER BY 
				name;
		";
		
		$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); 
		$query = $this->connection->prepare($sqlClosedOrganizations);
		$res = $query->execute($searchParams);
        $resData = $query->fetchAll(PDO::FETCH_ASSOC);
		$closedIdStr = '';
		$zpt = '';
		$closedOrganizationList = array();
		foreach ($resData as $row) {
			if (!($id =intval($row['id']))) {
				continue;
			}
			$closedIdStr .= $zpt . "'{$id}'";
			$zpt = ', ';
			$closedOrganizationList[$row['id']] = ['name' => $row['name']];
		}
		// Запросим расписание для закрытых органиаций с расчитом разницы времени относительно текущего момента
		$dayTimeNow = $searchParams[':day_of_week'] . " " . $searchParams[':time'];
		$sqlScheduleTimeLeft = "
			SELECT 
				*,
				SUBTIME(concat(day_of_week, ' ' , `open`), ?) AS  time_left
			FROM  schedule 
			WHERE 
				organization_id IN({$closedIdStr})
			ORDER BY 
				organization_id,
				day_of_week
		";
		$query = $this->connection->prepare($sqlScheduleTimeLeft);
		$res = $query->execute(array($dayTimeNow));
        $resData = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($resData as $row) {
			$id = $row['organization_id'];
			if (empty($closedOrganizationList[$id]['first_day_of_week'])) {
				// На всякий случай запомним первый рабочий день недели
				$closedOrganizationList[$id]['first_day_of_week'] = $row['day_of_week'];
				$closedOrganizationList[$id]['first_open']        = $row['open'];
				$closedOrganizationList[$id]['first_time_left']   = $row['time_left'];
			}
			if (empty($closedOrganizationList[$id]['next_day_of_week']) && mb_substr($row['time_left'], 0, 1) !== '-') {
				// И если нашелся на этой неделе рабочий день в будущем то запомним и его
				$closedOrganizationList[$id]['next_day_of_week'] = $row['day_of_week'];
				$closedOrganizationList[$id]['next_open']        = $row['open'];
				$closedOrganizationList[$id]['next_time_left']   = $row['time_left'];
			}
		}
		$ret = array();
		$curDayOfWeek = $searchParams[':day_of_week'];
		foreach ($closedOrganizationList as $id => $closedOrganization) {
			if (empty($closedOrganization['first_day_of_week'])) {
				continue;
			}
			if (!empty($closedOrganization['next_day_of_week'])) {
				$ret[$id]['opens_in'] = mb_substr($closedOrganization['next_time_left'],0,-3);
				
			} else {
				// Расчитаем разницу между текщим временем и открытием в первый раочйи день следующей недели
				$timeLeft = ($closedOrganization['first_day_of_week'] + 7 - $curDayOfWeek) * 24 * 60;
				$ex = explode(':', $searchParams[':time']);
				$curMinutes = intval($ex[0]) * 60 + intval($ex[1]);
				$ex = explode(':', $closedOrganization['first_open']);
				$openMinutes = intval($ex[0]) * 60 + intval($ex[1]);
				if ($curMinutes < $openMinutes) {
					$timeLeft += $openMinutes - $curMinutes; 
				} else {
					$timeLeft +=  $curMinutes - 24*60 - $openMinutes;
				}
				$minutes = $timeLeft % 60;
				$hours   = $timeLeft / 60;
				$ret[$id]['opens_in'] = sprintf("%02d:%02d",$hours, $minutes);
			}
			$ret[$id]['name'] = $closedOrganization['name']; 
			
		}		
		return array_values($ret);  
   }
}