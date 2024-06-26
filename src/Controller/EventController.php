<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/event')]
class EventController extends AbstractController
{
    #[Route('/all', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->json($eventRepository->findAll());
    }

    #[Route('/{id}/validate', name: 'app_event_validate', methods: ['POST'])]
    public function validateEvent(Event $event, Request $request, SerializerInterface $serializer): Response
    {
        if (sizeof($event->getComedians())<3) {
            return $this->json("You don't have the required comedians.");
        }
        if(!$event->getLocation()) {
            return$this->json("You don't have a location for your event !");
        }
        $event->setStatus(2);
        return $this->json("event validé");
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $event = $serializer->deserialize($request->getContent(), Event::class, 'json');
        $event->setComedyClub($this->getUser()->getComedyClub());
        $event->setStatus(0);

        // ajout verif pour pas avoir 2 specatcles du meme nom/date/lieu

        $event->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($event);
        $entityManager->flush();
        return $this->json($event, Response::HTTP_CREATED, [], ["groups"=>"event:read"]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->json($event, Response::HTTP_OK, [], ["groups"=>"event:read"]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer): Response
    {

        if ($this->getUser()->getComedyClub() != $event->getComedyClub()) {
            return $this->json("not your event to edit", Response::HTTP_FORBIDDEN);
        }

        $editedEvent = $serializer->deserialize($request->getContent(), Event::class, 'json');

        $event->setName($editedEvent->getName());
        $event->setDescription($editedEvent->getDescription());
        $event->setLocation($editedEvent->getLocation());
        $event->setDate($editedEvent->getDate());
      //  $event->set

        $entityManager->persist($event);
        $entityManager->flush();

        return $this->json($event, Response::HTTP_OK, [], ["groups" => "event:read"]);
    }

    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['DELETE'])]
    public function delete(Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()->getEvent() != $event) {
            return $this->json("you can't edit other event profiles", Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($event);
        $entityManager->flush();

        return $this->json('Event deleted', Response::HTTP_SEE_OTHER);
    }





}
