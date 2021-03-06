<?php

namespace FreeBet\Bundle\CompetitionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FreeBet\Bundle\CompetitionBundle\Document\Event;

/**
 * Description of EventController
 *
 * @author jobou
 */
class EventController extends Controller
{
    /**
     * Edit a match to set results
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \FreeBet\Bundle\CompetitionBundle\Document\Event $event
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Event $event)
    {
        $form = $this->createForm(
            $event->getFormType(),
            $event
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $om = $this->get('doctrine_mongodb.odm.default_document_manager');
            $om->persist($event);
            $om->flush();

            return $this->redirect(
                $this->generateUrl(
                    "competition_detail",
                    array(
                        "slug" => $event->getCompetition()->getSlug()
                    )
                )
            );
        }

        return $this->render('FreeBetCompetitionBundle:Event:edit.html.twig', array(
            'form' => $form->createView(),
            'event' => $event
        ));
    }

    /**
     * List the following events
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listNextAction()
    {
        $date = \FreeBet\Bundle\UIBundle\Services\DateManager::getUtcDateTime();
        $events = $this->get('free_bet.event.repository')->findNextEvents($date);

        return $this->render('FreeBetCompetitionBundle:Event:listNext.html.twig', array(
            'events' => $events
        ));
    }

    /**
     * List all gambles available for the event
     * Load all with slug to provide friendly url
     *
     * @param string $slugCompetition
     * @param string $slugEvent
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function betAction($slugCompetition, $slugEvent)
    {
        // Find the competition
        $competition = $this->get('free_bet.competition.repository')->findOneBySlug($slugCompetition);

        if (!$competition) {
            return $this->createNotFoundException('Competition '.$slugCompetition.' does not exists');
        }

        // Find the event in the competition
        $event = $this
            ->get('free_bet.event.repository')
            ->findOneBy(array('slug'=>$slugEvent, 'competition.id'=>$competition->getId()));

        if (!$event) {
            return $this->createNotFoundException('Match '.$slugEvent.' does not exists');
        } elseif ($event->isStarted()) {
            throw new AccessDeniedException('The event has started. You cannot bet on it.');
        }

        return $this->render('FreeBetCompetitionBundle:Event:bet.html.twig', array(
            'event' => $event,
            'competition' => $competition
        ));
    }
}
