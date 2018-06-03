<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\Expr;

use App\Entity\User;
use App\Entity\Visit;

class StatsController extends Controller
{
    /**
     * @Route("/stats", name="stats")
     */
    public function index()
    {
    	$entityManager = $this->getDoctrine()->getManager();
    	$users = $this->getDoctrine()->getRepository(User::class);
	$countThisMonth = $entityManager
	    ->createQuery('SELECT COUNT(u.id) as count, u.city FROM App\Entity\User u where u.created > :date GROUP BY u.city')
	    ->setParameter('date', new \DateTime('-1 month'))
	;

    	$visits = $this->getDoctrine()->getRepository(visit::class);
	$countUniqueVisits = array();
	for ($i = 0; $i < 7; $i++){
	    $j = $i - 1;
	    $selection = $entityManager
		->createQuery('SELECT count(distinct v.ip) as count FROM App\Entity\Visit v where v.date > :startDay and v.date < :endDay')
		->setParameter('startDay', (new \DateTime("{$i} days ago"))->setTime(0,0))
		->setParameter('endDay', (new \DateTime("{$j} days ago"))->setTime(0,0))
		->execute()
	    ;
	    $selection = $selection[0];
	    $selection['date'] = (new \DateTime("{$i} days ago"))->format('Y-m-d');
	    $countUniqueVisits[] = $selection;
	}
/*
	$userList = $entityManager
	    ->createQuery('SELECT distinct v.user_id as userId, u.firstName, u.secondName  
		FROM App\Entity\Visit v LEFT JOIN App\Entity\User u with v.date > :startDay and v.user_id != \'NULL\'')
	    ->setParameter('startDay', (new \DateTime("6 days ago"))->setTime(0,0))
	    ->execute()
	;
*/

	$qb = $entityManager->createQueryBuilder('v');
	$qb->select('distinct v.user_id as userId', 'u.firstName', 'u.secondName')
	    ->from(
		'App\Entity\Visit',
		'v'
	    )
	    ->leftJoin(
		'App\Entity\User',
		'u',
		\Doctrine\ORM\Query\Expr\Join::WITH,
		'u.id = v.user_id'
	    )
	    ->where('v.date > :startDay')
	    ->setParameter('startDay', (new \DateTime("6 days ago"))->setTime(0,0))
	;
	$query = $qb->getQuery();
	$userList = $query->getResult();

	foreach ($userList as $listId => $user){
	    $visits = array();
	    if (!$user['userId']){
		$userList[$listId]['firstName'] = 'Guest';
	    }
	    for ($i = 0; $i < 7; $i++){
		$j = $i - 1;
		$selection = $entityManager
		    ->createQuery('SELECT count(distinct v.id) as count FROM App\Entity\Visit v where v.user_id = :userId and v.date > :startDay and v.date < :endDay')
		    ->setParameter('userId', $user['userId'])
		    ->setParameter('startDay', (new \DateTime("{$i} days ago"))->setTime(0,0))
		    ->setParameter('endDay', (new \DateTime("{$j} days ago"))->setTime(0,0))
		    ->execute()
		;
		$selection = $selection[0];
		$selection['date'] = (new \DateTime("{$i} days ago"))->format('Y-m-d');
		$visits[] = $selection;
	    }
	    $userList[$listId]['visits'] = $visits;
	    unset($userList[$listId]['userId']);
	}

        return $this->render('stats/index.html.twig', [
            'controller_name' => 'StatsController',
	    'countThisMonth' => $countThisMonth->execute(),
	    'countUniqueVisits' => $countUniqueVisits,
	    'userStatsThisWeek' => $userList
        ]);
    }
}