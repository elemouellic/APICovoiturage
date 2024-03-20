<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Student;
use App\Entity\Trip;
use App\Utils\Utils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class TripController extends AbstractController
{
    /**
     * Insert a new trip
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     * @throws Exception
     */
#[Route('/inserttrajet', name: 'app_trip_insert', methods: ['POST'])]
public function insertTrip(Request $request, EntityManagerInterface $em): JsonResponse
{
    // Serialize the request data
    $data = Utils::serializeRequestData($request);

    // Check if all necessary fields are present and not empty
    if (empty($data['drive_id']) || empty($data['start_id']) || empty($data['arrive_id']) || empty($data['kmdistance']) || empty($data['traveldate']) || empty($data['placesoffered'])) {
        throw new HttpException(400, 'Missing required fields.');
    } else {

        try{
            // Create a new trip
            $trip = new Trip();

            // Get the Student object for the drive_id
            $drive = $em->getRepository(Student::class)->find($data['drive_id']);
            if (!$drive) {
                throw new HttpException(404, 'Driver not found');
            }

            // Get the City objects for the start_id and arrive_id
            $start = $em->getRepository(City::class)->find($data['start_id']);
            if (!$start) {
                throw new HttpException(404, 'Start city not found');
            }

            $arrive = $em->getRepository(City::class)->find($data['arrive_id']);
            if (!$arrive) {
                throw new HttpException(404, 'Arrival city not found');
            }

            // Set the trip attributes
            $trip->setDrive($drive);
            $trip->setStart($start);
            $trip->setArrive($arrive);
            $trip->setKmDistance($data['kmdistance']);
            $trip->setTravelDate(new DateTime($data['traveldate']));
            $trip->setPlacesOffered($data['placesoffered']);

            // Persist the trip
            $em->persist($trip);
            $em->flush();
        } catch (Exception $e) {
            throw new HttpException(400, "Error while creating the trip: " . $e->getMessage());
        }

        // Return the created trip data
        return $this->json([
            'id' => $trip->getId(),
            'drive_id' => $trip->getDrive()->getId(),
            'start_id' => $trip->getStart()->getId(),
            'arrive_id' => $trip->getArrive()->getId(),
            'kmdistance' => $trip->getKmDistance(),
            'traveldate' => $trip->getTravelDate()->format('Y-m-d H:i:s'),
            'placesoffered' => $trip->getPlacesOffered(),
        ], 201);
    }
}}
