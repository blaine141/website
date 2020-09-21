<?php

namespace Drupal\ozone_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use \Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\Query\QueryFactory;

class OzoneAnalyticsController extends ControllerBase {

	public function content() {
		return array (
			'#type' => 'markup',
			'#markup' => $this->t('Go Ozone!'),
		);
	}


	public function calculate1 () {
		//$stuff = Views::viewsData()->get('stuff');

	    // Setup an empty response, so for example, the Content-Disposition header
	    // can be set.
	    $response = new HtmlResponse('', 200);


	    //$output = gettype($stuff);
        $output = "1,2,3,4\n5,6,7,8";
		//$output = $stuff;

	    $response->setContent($output);

	    $response->headers->set('Content-type', "text/text");

	    return $response;
	}

	public function createScoutReport(Request $request) {

		// We don't want to cache this page since it is always creating something
		\Drupal::service('page_cache_kill_switch')->trigger();
		
		//Check request to see if data was sent
		$params = array();
		$content = $request->getContent();
		
		//If there is data, decode the json
		if (!empty($content)) {
			
			//Make array from json
			$params = json_decode($content, TRUE);
			
			$errorText = "";
			if($params['scouter'] == "")
			{
				$errorText = "Scouter Error";
			}
			if(!is_numeric($params['match']) || $params['match'] < 1 )
			{
				$errorText = "Match Error";
			}
			if(!($params['alliance']=="red" || $params['alliance']=="blue" || $params['alliance']=="both" ||  $params['alliance']==""))
			{
				if($params['alliance']=="")
					$params['alliance']=="both";
				else
					$errorText = "Invalid Alliance";
			}
			if($params['times']!= null)
			{
				if(!is_numeric($params['team']) || $params['team'] < 1 )
				{
					$errorText = "Team Error";
				}
				if($params['ballScout'] != null)
				{
					$errorText = "Both Scout Types";
				}
				if($params['autoperiod'] == null || count($params['autoperiod']) != 1)
				{
					$errorText = "Invalid Auto Data";
				}
				else{
					if(!($params['autoperiod'][0]['gear']=="fail" || $params['autoperiod'][0]['gear']=="success"))
					{
						$errorText = "Invalid Auto Gear";
					}
					if(!($params['autoperiod'][0]['passedLine']==false || $params['autoperiod'][0]['passedLine']==true))
					{
						$errorText = "Invalid Auto Line";
					}
					if(!is_numeric($params['autoperiod'][0]['ballPoints']) || $params['autoperiod'][0]['ballPoints']<0)
					{
						$errorText = "Invalid Auto Fuel Points";
					}
				}
				if($params['defense'] == null || count($params['defense']) != 1)
				{
					$errorText = "Invalid Defense Data";
				}
				foreach($params['times'] as $time)
				{
					if(!is_numeric($time['start']) || $time['start']<0)
					{
						$errorText = "Invalid event Start Time";
					}
					if(!is_numeric($time['stop']) || $time['stop']<0)
					{
						$errorText = "Invalid event Stop Time";
					}
					if(!($time['action']=="gear"||$time['action']=="collect"||$time['action']=="shoot"||$time['action']=="climb"||$time['action']=="defense"))
					{
						$errorText = "Invalid Action Type";
					}
					if(!($time['result']=="" || $time['result']=="fail" || $time['result']=="success"))
					{
						$errorText = "Invalid result";
					}
				}
			} else if($params['ballScout'] != null)
			{
				if($params['times'] != null)
				{
					$errorText = "Both Scout Types";
				}
				foreach($params['ballScout'] as $update)
				{
					if(!($update['alliance']=="red" || $update['alliance'] == "blue"))
					{
						$errorText = "Invalid Fuel Update Alliance";
					}
					if(!is_numeric($update['stop']) || $update['stop']<0)
					{
						$errorText = "Invalid Fuel Update Time";
					}
					if(!is_numeric($update['points']) || $update['points']<0)
					{
						$update['points'] = 0;
					}
				}
			} else
			{
			}
			if($errorText!="")
			{
				$report = Node::create(array(
					'type' => 'team_info',
					'title' => 'Error',
					'field_post_notes' => $errorText,
					'field_pre_notes' => $content
				));
				$report->save();
				$response = new HtmlResponse('', 200);
				$response->setContent($errorText);
				$response->headers->set('Content-type', "text/text");
				return $response;
			}
			//Get all match entities
			$matchids = \Drupal::entityQuery('node')
			->condition('type', 'scouting_match')
			->execute();
			
			//If there are matches
			if(count($matchids)!=0)
			{
				//Get nodes from entities
				$matches =  \Drupal\node\Entity\Node::loadMultiple($matchids);
				
				//Search nodes to see if match already exists then save it in currentMatch 
				foreach($matches as $match)
				{
					if($params['match'] == $match->get('field_match_number')->value)
						$currentMatch = $match;
				}
			}
			
			//If not already made, make it
			if(!$currentMatch)
			{
				//Create Match
				$report = Node::create(array(
					'type' => 'scouting_match',
					'title' => 'Match ' . $params['match'],
					'field_match_number' => $params['match'],
					'field_reports' => array()
				));
				$report->save();
				
				//Set currentMatch to the match of this record
				$currentMatch = $report;
				
				//Make a blue alliance 
				$report = Node::create(array(
					'type' => 'alliance',
					'title' => 'Blue Alliance',
					'field_is_red' => false,
					'field_teams' => array()
				));
				$report->save();
				
				//Link blue alliance to Match
				$currentMatch->	field_alliances[0] =['target_id' => $report->get('nid')->value];
				
				//Make a red alliance
				$report = Node::create(array(
					'type' => 'alliance',
					'title' => 'Red Alliance',
					'field_is_red' => true,
					'field_teams' => array()
				));
				$report->save();
				
				//Link red alliance to Match
				$currentMatch->	field_alliances[1] =['target_id' => $report->get('nid')->value];
				
				//Update changes to match
				$currentMatch->save();
			}
			

			
			//Find alliance and set currentAlliance
			for($index = 0; $index<count($currentMatch->field_alliances);$index++)
			{
				$alliance =  \Drupal\node\Entity\Node::load($currentMatch->field_alliances[$index]->target_id);
				if($alliance->get('field_is_red')->value==($params['alliance']=="red"))
					$currentAlliance = $alliance;
			}

			//If there is data on fuel, report fuel data
			if($params['ballScout'] != null)
			{
				
				//Make a fuel info node
				$report = Node::create(array(
					'type' => 'fuel_scout',
					'title' => 'Fuel Scout ' . $params['scouter'] . ' Match: ' . $params['match'],
					'field_sco' => $params['scouter'],
					'field_updates' => array()
					));
				$report->save();
				
				//if the fuel scout is reporting for both team, link the scouter info to both alliances
				if($params['alliance']=="both")
				{
					//load the blue alliance
					$alliance = Node::load($currentMatch->field_alliances[0]->target_id);
					
					//Set fuel scout
					$alliance->field_fuel_scout[count($alliance->field_fuel_scout)] =['target_id' => $report->get('nid')->value];
					$alliance->save();
					
					//load red alliance
					$alliance = Node::load($currentMatch->field_alliances[1]->target_id);
					
					//Set fuel scout
					$alliance->field_fuel_scout[count($alliance->field_fuel_scout)] =['target_id' => $report->get('nid')->value];
					$alliance->save();
				}
				else	//Otherwise, link the souter data to the currentAlliance
				{
					$currentAlliance ->field_fuel_scout[count($currentAlliance->field_fuel_scout)] =['target_id' => $report->get('nid')->value];
					$currentAlliance->save();
				}
				
				//Set currentFuelScout
				$currentFuelScout = $report;
				
				//Go through all fuel scout updates
				foreach($params['ballScout'] as $update)
				{
					//Make a fuel update node
					$report = Node::create(array(
						'type' => 'fuel_update',
						'title' => $update['alliance'] . ' Fuel Points: ' . $update['points'],
						'field_score' => $update['points'],
						'field_time' => $update['stop'],
						'field_is_red' => ($update['alliance']=='red')
					));
					$report->save();
					
					//Link it to the scouter data
					$currentFuelScout ->field_updates[count($currentFuelScout ->field_updates)] =['target_id' => $report->get('nid')->value];
					$currentFuelScout->save();
				}
			}
			
			//If a team scouter
			elseif($params['defense']!=null)
			{
				//Look for team
				$previousRecords = 0;
				for($index = 0; $index<count($currentAlliance->field_teams);$index++)
				{
					$team =  \Drupal\node\Entity\Node::load($currentAlliance->field_teams[$index]->target_id);
					if($team->get('field_number')->value%100000==($params['team']))
						$previousRecords ++;
				}
				
				//If not already made, make it
				if($previousRecords >0)
				{
					$params['team'] += 100000*$previousRecords;
				}
				//Make team node
				$report = Node::create(array(
					'type' => 'team_info',
					'title' => 'Team ' . $params['team'] . ' Match ' . $params['match'],
					'field_number' => $params['team'],
					'field_improvements' => $params['improvements'],
					'field_post_notes' => $params['postnotes'],
					'field_pre_notes' => $params['prenotes'],
					'field_scouter' => $params['scouter'],
					'field_needs_review' => $params['needsReview'],
					'field_auto_info' => array(),
					'field_defense_info' => array(),
					'field_events' => array()
				));
				$report->save();
				
				//Link the alliance to it
				$currentAlliance->field_teams[count($currentAlliance->field_teams)] =['target_id' => $report->get('nid')->value];
				$currentAlliance->save();
				
				//Set currentTeam
				$currentTeam = $report;
				
				//Create Auto info
				$report = Node::create(array(
					'type' => 'auto_info',
					'title' => 'Auto Info',
					'field_crossed_line' => $params['autoperiod'][0]['passedLine'],
					'field_fuel_points' => $params['autoperiod'][0]['ballPoints'],
					'field_gear' => $params['autoperiod'][0]['gear'],
					'field_start_position' => $params['startpos']
				));
				$report->save();
				
				//Link to team
				$currentTeam->field_auto_info =['target_id' => $report->get('nid')->value];
				$currentTeam->save();
				
				//Create Defense Object
				$report = Node::create(array(
					'type' => 'defense_info',
					'title' => 'Defense Info',
					'field_bottom' => $params['defense'][0]['bottom'],
					'field_cro' => $params['defense'][0]['crossLeft'],
					'field_cross_right' => $params['defense'][0]['crossRight'],
					'field_left' => $params['defense'][0]['left'],
					'field_right' => $params['defense'][0]['right'],
					'field_top' => $params['defense'][0]['top']
				));
				$report->save();
				
				//Link to team
				$currentTeam->field_defense_info =['target_id' => $report->get('nid')->value];
				$currentTeam->save();
				
				$currentTeam->field_events = array();
				
				//Loop through all events
				foreach($params['times'] as $time)
				{
					//Create Event
					$report = Node::create(array(
						'type' => 'event',
						'title' => $time['action'],
						'field_action' => $time['action'],
						'field_result' => $time['result'],
						'field_start' => $time['start'],
						'field_stop' => $time['stop']
					));
					$report->save();
					
					//Link to the team
					$currentTeam->field_events[count($currentTeam->field_events)] =['target_id' => $report->get('nid')->value];
					$currentTeam->save();
				}
			}
			$response = new HtmlResponse('', 200);
			$response->setContent("$text All good, everything checks out");
			$response->headers->set('Content-type', "text/text");
			return $response;
		}	
		else
		{
			$response = new HtmlResponse('', 200);
			$response->setContent("No Content");
			$response->headers->set('Content-type', "text/text");
			return $response;
		}
		//Send positive response
		
	}
	
	public function error_catcher(Request $request) {

		// We don't want to cache this page since it is always creating something
		\Drupal::service('page_cache_kill_switch')->trigger();
		
		//Check request to see if data was sent
		$params = array();
		$content = $request->getContent();
		
		//If there is data, decode the json
		if (!empty($content)) {
			
			//Make array from json
			$params = json_decode($content, TRUE);
			
			$errorText = "";
			if($params['scouter'] = "")
			{
				$errorText = "Scouter Error";
			}
			if(!is_numeric($params['match']) || $params['match'] < 1 )
			{
				$errorText = "Match Error";
			}
			if(!is_numeric($params['team']) || $params['team'] < 1 )
			{
				$errorText = "Team Error";
			}
			if(!($params['alliance']=="red" || $params['alliance']=="blue" || $params['alliance']=="both" ))
			{
				$errorText = "Invalid Alliance";
			}
			if($params['times']!= null)
			{
				if($params['ballScout'] != null)
				{
					$errorText = "Both Scout Types";
				}
				if($params['autoperiod'] == null || count($params['autoperiod']) != 1)
				{
					$errorText = "Invalid Auto Data";
				}
				else{
					if(!($params['autoperiod'][0]['gear']=="fail" || $params['autoperiod'][0]['gear']=="success"))
					{
						$errorText = "Invalid Auto Gear";
					}
					if(!($params['autoperiod'][0]['passedLine']==false || $params['autoperiod'][0]['passedLine']==true))
					{
						$errorText = "Invalid Auto Line";
					}
					if(!is_numeric($params['autoperiod'][0]['ballPoints']) || $params['autoperiod'][0]['ballPoints']<0)
					{
						$errorText = "Invalid Auto Fuel Points";
					}
				}
				if($params['defense'] == null || count($params['defense']) != 1)
				{
					$errorText = "Invalid Defense Data";
				}
				foreach($params['times'] as $time)
				{
					if(!is_numeric($time['start']) || $time['start']<0)
					{
						$errorText = "Invalid event Start Time";
					}
					if(!is_numeric($time['stop']) || $time['stop']<0)
					{
						$errorText = "Invalid event Stop Time";
					}
					if(!($time['action']=="gear"||$time['action']=="collect"||$time['action']=="shoot"||$time['action']=="climb"||$time['action']=="defense"))
					{
						$errorText = "Invalid Action Type";
					}
					if(!($time['result']=="" || $time['result']=="fail" || $time['result']=="success"))
					{
						$errorText = "Invalid result";
					}
				}
			} else if($params['ballScout'] != null)
			{
				if($params['times'] != null)
				{
					$errorText = "Both Scout Types";
				}
				foreach($params['ballScout'] as $update)
				{
					if(!($update['alliance']=="red" || $update['alliance'] == "blue"))
					{
						$errorText = "Invalid Fuel Update Alliance";
					}
					if(!is_numeric($update['stop']) || $update['stop']<0)
					{
						$errorText = "Invalid Fuel Update Time";
					}
					if(!is_numeric($update['points']) || $update['points']<0)
					{
						$errorText = "Invalid Fuel Update Points";
					}
				}
			} else
			{
			}
			if($errorText!="")
			{
				$report = Node::create(array(
					'type' => 'team_info',
					'title' => 'Error',
					'field_post_notes' => $errorText,
					'field_pre_notes' => $content
				));
				$report->save();
				$response = new HtmlResponse('', 200);
				$response->setContent($errorText);
				$response->headers->set('Content-type', "text/text");
				return $response;
			}
			
			$response = new HtmlResponse('', 200);
			$response->setContent("All Good");
			$response->headers->set('Content-type', "text/text");
			return $response;
		}

		
	}
	
	public function matches(Request $request) {
		
		//Initialize output array
		$matchesArray = array(
			'matches' => array()
		);
		$params = array();
		$content = $request->getContent();
		
		//If there is data, decode the json
		if (!empty($content)) {
			
			//Make array from json
			$params = json_decode($content, TRUE);
			$matchLimitB = $params['matchB'];
			$matchLimitT = $params['matchT'];
		}
			
		$matchids = \Drupal::entityQuery('node')
			->condition('type', 'team_info')
			->execute();
			
		if(count($matchids)!=0)
		{
			//Get nodes from entities
			$matches =  \Drupal\node\Entity\Node::loadMultiple($matchids);
				
			//Search nodes to see if match already exists then save it in currentMatch 
			
			foreach($matches as $match)
			{
				if (substr($match->title->value, 0, 4) === 'Team' && $match->get('field_number')->value < 10000 && substr($match->title->value, strrpos($match->title->value , "Match ")+5)>$matchLimitB && substr($match->title->value, strrpos($match->title->value , "Match ")+5)<=$matchLimitT) {
				    \Drupal::logger('my_module')->notice("processing [" . $match->title->value . ']'	);
				} else {
					continue;
				}
				
				if($match->get('field_number')->value)
				{
					$currentMatch = array();
					$currentMatch['team_number'] = $match->get('field_number')->value;
					$currentMatch['pre_notes'] = $match->get('field_pre_notes')->value;
					$currentMatch['improvements'] = $match->get('field_improvements')->value;
					$currentMatch['post_notes'] = $match->get('field_post_notes')->value;
					$currentMatch['events'] = array();
			//\Drupal::logger('my_module')->notice("pre loop"	);

					for($index = 0; $match->field_events && $index<count($match->field_events);$index++)
					{
			//\Drupal::logger('my_module')->notice("pre event load [" . 	$match->field_events[$index]->target_id . ']');
			

						$event =  \Drupal\node\Entity\Node::load($match->field_events[$index]->target_id);
			//\Drupal::logger('my_module')->notice("post event load"	);

						if ($event) {
							$currentEvent = array(
							'start' => $event->field_start->value,
							'stop' => $event->field_stop->value,
							'result' => $event->field_result->value,
							'action' => $event->field_action->value
						);
						if ($currentEvent)
							array_push($currentMatch['events'],$currentEvent);
						}
						
						unset($event);
					}
			//\Drupal::logger('my_module')->notice("post loop"	);

					
					if(count($match->field_defense_info)!=0)
					{
						$defense = \Drupal\node\Entity\Node::load($match->field_defense_info[0]->target_id);
						$currentMatch['defense_bottom'] = $defense->field_bottom->value;
						$currentMatch['defense_cross_left'] = $defense->field_cro->value;
						$currentMatch['defense_cross_right'] = $defense->field_cross_right->value;
						$currentMatch['defense_left'] = $defense->field_left->value;
						$currentMatch['defense_right'] = $defense->field_right->value;
						$currentMatch['defense_top'] = $defense->field_top->value;
					}
					if(count($match->field_auto_info)!=0)
					{
						$auto = \Drupal\node\Entity\Node::load($match->field_auto_info[0]->target_id);
						$currentMatch['auto_fuel_points'] = $auto->field_fuel_points->value;
						$currentMatch['auto_gear'] = $auto->field_gear->value;
						$currentMatch['auto_crossed_line'] = $auto->field_crossed_line->value;
					}
					$currentMatch['match_num']= substr($match->title->value, strrpos($match->title->value , "Match ")+5);
					
					/*$matchids = \Drupal::entityQuery('node')
						->condition('type', 'scouting_match')
						->execute();
					if(count($matchids)!=0)
					{
						//$currentMatch['found']= 'matches';
						$matchesToSearch =  \Drupal\node\Entity\Node::loadMultiple($matchids);
						foreach($matchesToSearch as $matcht)
						{
							if(count($matcht->field_alliances)>0)
							{
								$alliance = \Drupal\node\Entity\Node::load($matcht->field_alliances[0]->target_id);
								if($alliance)
								{
									foreach($alliance->field_teams as $teamPointer)
									{
										if($teamPointer->target_id == $match->get('nid')->value)
										{
											$currentMatch['alliance'] = $alliance->get('field_is_red')->value;
											$currentMatch['match_num']= $matcht->get('field_match_number')->value;
										}
									}
								}
							}
							if(count($matcht->field_alliances)>1)
							{
								$alliance = \Drupal\node\Entity\Node::load($matcht->field_alliances[1]->target_id);
								if($alliance)
								{
									foreach($alliance->field_teams as $teamPointer)
									{
										if($teamPointer->target_id == $match->get('nid')->value)
										{
											$currentMatch['alliance'] = $alliance->get('field_is_red')->value;
											$currentMatch['match_num'] = $matcht->get('field_match_number')->value;
										}
									}
								}
							}
							unset($matcht);
						}
						unset($matchesToSearch);
					}*/
					
				}
				array_push($matchesArray['matches'],$currentMatch);
				unset($match);
			}
		}
		$response = new HtmlResponse('', 200);
	    $response->setContent(json_encode($matchesArray));
	    $response->headers->set('Content-type', "text/text");
	    return $response;
	}
	public function assignment_upload(Request $request) {
		
		$params = array();
		$content = $request->getContent();
		$text = 0;
		
		//get all assignments
		$assignmentids = \Drupal::entityQuery('node')
			->condition('type', 'assignment')
			->execute();
			
		//If there are assignments
		if(count($assignmentids)!=0)
		{
			//Get nodes from entities
				$assignments =  \Drupal\node\Entity\Node::loadMultiple($assignmentids);
			
			//go through each assignment
			foreach($assignments as $assignment)
			{
				
				//Delete that assignment
				$assignment->delete();
			}
		}
			
		//If there is data, decode the json
		if (!empty($content)) {
			
			//Make array from json
			$params = json_decode($content, TRUE);
			foreach($params as $assignment)
			{
				$text++;
				//Create Assignment
				$report = Node::create(array(
					'type' => 'assignment',
					'title' => 'Match ' . $assignment['match'] . " assignments",
					'field_match_number' => $assignment['match'],
					'field_name1' => $assignment['name1'],
					'field_name2' => $assignment['name2'],
					'field_name3' => $assignment['name3'],
					'field_name4' => $assignment['name4'],
					'field_name5' => $assignment['name5'],
					'field_name6' => $assignment['name6'],
					'field_fuel' => $assignment['fuel'],
					'field_blue1' => $assignment['blue1'],
					'field_blue2' => $assignment['blue2'],
					'field_blue3' => $assignment['blue3'],
					'field_red1' => $assignment['red1'],
					'field_red2' => $assignment['red2'],
					'field_red3' => $assignment['red3']
				));
				$report->save();
			}
		}
		
		
		$response = new HtmlResponse('', 200);
	    $response->setContent($text . " matches uploaded");
	    $response->headers->set('Content-type', "text/text");
	    return $response;
	}
	public function assignments() {
		
		$outArray = array();
		
		//get all assignments
		$assignmentids = \Drupal::entityQuery('node')
			->condition('type', 'assignment')
			->execute();
			
		//If there are assignments
		if(count($assignmentids)!=0)
		{
			//Get nodes from entities
				$assignments =  \Drupal\node\Entity\Node::loadMultiple($assignmentids);
			
			//go through each assignment
			foreach($assignments as $assignment)
			{
				for($index = 0; $index<count($assignment->field_name1);$index++)
				{
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_name1[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "team";
					$assignmentObject['team_number'] = $assignment->field_blue1->value;
					$assignmentObject['alliance'] = "blue";
					array_push($outArray,$assignmentObject);
				}
				for($index = 0; $index<count($assignment->field_name2);$index++)
				{
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_name2[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "team";
					$assignmentObject['team_number'] = $assignment->field_blue2->value;
					$assignmentObject['alliance'] = "blue";
					array_push($outArray,$assignmentObject);
				}
				for($index = 0; $index<count($assignment->field_name3);$index++)
				{
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_name3[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "team";
					$assignmentObject['team_number'] = $assignment->field_blue3->value;
					$assignmentObject['alliance'] = "blue";
					array_push($outArray,$assignmentObject);
				}
				for($index = 0; $index<count($assignment->field_name4);$index++)
				{
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_name4[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "team";
					$assignmentObject['team_number'] = $assignment->field_red1->value;
					$assignmentObject['alliance'] = "red";
					array_push($outArray,$assignmentObject);
				}
				for($index = 0; $index<count($assignment->field_name5);$index++)
				{
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_name5[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "team";
					$assignmentObject['team_number'] = $assignment->field_red2->value;
					$assignmentObject['alliance'] = "red";
					array_push($outArray,$assignmentObject);
				}
				for($index = 0; $index<count($assignment->field_name6);$index++)
				{
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_name6[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "team";
					$assignmentObject['team_number'] = $assignment->field_red3->value;
					$assignmentObject['alliance'] = "red";
					array_push($outArray,$assignmentObject);
				}
				for($index = 0; $index<count($assignment->field_fuel);$index++)
				{					
					$assignmentObject=array();
					$assignmentObject['scouter'] = $assignment->field_fuel[$index]->value;
					$assignmentObject['match'] = $assignment->field_match_number->value;
					$assignmentObject['assignment'] = "fuel";
					array_push($outArray,$assignmentObject);
				}
			}
		}
		
		$response = new HtmlResponse('', 200);
	    $response->setContent(json_encode($outArray));
	    $response->headers->set('Content-type', "text/text");
	    return $response;
	}
	public function fuel_reports() {
		
		$outArray = array();
		
		//get all assignments
		$matchids = \Drupal::entityQuery('node')
			->condition('type', 'scouting_match')
			->execute();
			
		//If there are assignments
		if(count($matchids)!=0)
		{
			//Get nodes from entities
			$matches = \Drupal\node\Entity\Node::loadMultiple($matchids);
			
			//go through each assignment
			foreach($matches as $match)
			{
				$fuelObject=array();
				$blueAlliance = \Drupal\node\Entity\Node::load($match->field_alliances[0]->target_id);
				$redAlliance = \Drupal\node\Entity\Node::load($match->field_alliances[1]->target_id);
				$fuelObject['match'] = $match->field_match_number->value;
				if($blueAlliance->field_fuel_scout[0]->target_id == $redAlliance->field_fuel_scout[0]->target_id)
				{
					$fuelScout = \Drupal\node\Entity\Node::load($redAlliance->field_fuel_scout[0]->target_id);
					$fuelObject['updates'] = array();
					for($index = 0; $index<count($fuelScout->field_updates);$index++)
					{
						$update =  \Drupal\node\Entity\Node::load($fuelScout->field_updates[$index]->target_id);
						$currentUpdate = array(
							'alliance' => $update->field_is_red->value,
							'time' => $update->field_time->value,
							'score' => $update->field_score->value
						);
						array_push($fuelObject['updates'],$currentUpdate);
					}
					array_push($outArray,$fuelObject);
				} 
				else 
				{
					$fuelObject['updates'] = array();
					$fuelScout = \Drupal\node\Entity\Node::load($redAlliance->field_fuel_scout[0]->target_id);
					for($index = 0; $index<count($fuelScout->field_updates);$index++)
					{
						$update =  \Drupal\node\Entity\Node::load($fuelScout->field_updates[$index]->target_id);
						$currentUpdate = array(
							'alliance' => $update->field_is_red->value,
							'time' => $update->field_time->value,
							'score' => $update->field_score->value
						);
						array_push($fuelObject['updates'],$currentUpdate);
					}
					$fuelScout = \Drupal\node\Entity\Node::load($blueAlliance->field_fuel_scout[0]->target_id);
					for($index = 0; $index<count($fuelScout->field_updates);$index++)
					{
						$update =  \Drupal\node\Entity\Node::load($fuelScout->field_updates[$index]->target_id);
						$currentUpdate = array(
							'alliance' => $update->field_is_red->value,
							'time' => $update->field_time->value,
							'score' => $update->field_score->value
						);
						array_push($fuelObject['updates'],$currentUpdate);
					}
					array_push($outArray,$fuelObject);
					
				}
			}
		}
		
		$response = new HtmlResponse('', 200);
	    $response->setContent('{"reports":' . json_encode($outArray)."}");
	    $response->headers->set('Content-type', "text/text");
	    return $response;
	}
	public function team_calcs() {
		$outArray = array();
		$teamids = \Drupal::entityQuery('node')
			->condition('type', 'team_calculated_data')
			->execute();
		if(count($teamids)!=0)
		{
			//Get nodes from entities
			$teams = \Drupal\node\Entity\Node::loadMultiple($teamids);
			
			//go through each assignment
			foreach($teams as $team)
			{
				$teamData = array();
				$teamData['number'] = $team->field_number->value;
			}
		}
		$response = new HtmlResponse('', 200);
	    $response->setContent('{"reports":' . json_encode($outArray)."}");
	    $response->headers->set('Content-type', "text/text");
	    return $response;
	}
	public function team_calc_upload(Request $request) {
		
		$params = array();
		$content = $request->getContent();
		$text = 0;
		
		//get all assignments
		
			
		//If there is data, decode the json
		if (!empty($content)) {
			
			
			
			$teamids = \Drupal::entityQuery('node')
				->condition('type', 'team_calculated_data')
				->execute();
			
			//If there are assignments
			if(count($teamids)!=0)
			{
				//Get nodes from entities
					$teams =  \Drupal\node\Entity\Node::loadMultiple($teamids);
				
				//go through each assignment
				foreach($teams as $team)
				{
					
					//Delete that assignment
					$team->delete();
				}
			}
			
			//Make array from json
			$params = json_decode($content, TRUE);
			foreach($params as $team)
			{
				$text++;
				//Create Assignment
				$report = Node::create(array(
					'type' => 'team_calculated_data',
					'title' => 'Team ' . $team['num'],
					'field_number' => $team['num'],
					'field_average_auto_fuel' => $team['averageAutoFuel'],
					'field_average_auto_gear' => $team['averageAutoGear'],
					'field_average' => $team['averageFuelScore'],
					'field_average_fuel_time' => $team['averageFuelTime'],
					'field_average_gears' => $team['averageGears'],
					'field_average_gear_time' => $team['averageGearTime'],
					'field_climb_accuracy' => $team['climbAccuracy'],
					'field_climb_time' => $team['climbTime'],
					'field_cross_line_accuracy' => $team['crossLineAccuracy'],
					'field_fuel_efficiency' => $team['fuelEfficiency'],
					'field_gear_effe' => $team['gearEfficiency']
				));
				$report->save();
			}
		}
		
		
		$response = new HtmlResponse('', 200);
	    $response->setContent($text . " teams uploaded");
	    $response->headers->set('Content-type', "text/text");
	    return $response;
	}
}