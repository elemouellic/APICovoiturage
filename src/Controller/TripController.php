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
     * @throws Exception If an error occurs
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

            try {
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
    }

    /**
     * Insert a new participation
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     * @throws Exception If an error occurs
     */
    #[Route('/insertinscription', name: 'app_trip_insert_participation', methods: ['POST'])]
    public function insertParticipation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Serialize the request data
        $data = Utils::serializeRequestData($request);

        // Check if all necessary fields are present and not empty
        if (empty($data['student_id']) || empty($data['trip_id'])) {
            throw new HttpException(400, 'Missing required fields.');
        }

        try {
            // Get the Student object for the student_id
            $student = $em->getRepository(Student::class)->find($data['student_id']);
            if (!$student) {
                throw new HttpException(404, 'Student not found');
            }

            // Get the Trip object for the trip_id
            $trip = $em->getRepository(Trip::class)->find($data['trip_id']);
            if (!$trip) {
                throw new HttpException(404, 'Trip not found');
            }

            // Check if the trip is already full
            if (count($trip->getParticipate()) >= $trip->getPlacesOffered()) {
                throw new HttpException(400, 'The trip is already full');
            }

            // Check if the student is already participating in the trip
            if ($student->getParticipate()->contains($trip)) {
                throw new HttpException(400, 'The student is already participating in the trip');
            }

            // Set the participation attributes
            $student->addParticipate($trip);

            // Persist the participation
            $em->persist($student);
            $em->flush();
        } catch (Exception $e) {
            throw new HttpException(400, "Error while creating the participation: " . $e->getMessage());
        }

        // Return the created participation data
        return $this->json([
            'trip_id' => $trip->getId(),
            'student_id' => $student->getId(),
        ], 201);
    }

    /**
     * Search for trips
     * @param Request $request The request object
     * @param int $idCityStart The start city id
     * @param int $idCityArrival The arrival city id
     * @param string $dateTravel The travel date
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     * @throws Exception
     */
    #[Route('/recherchetrajet/{idCityStart}/{idCityArrival}/{dateTravel}', name: 'app_trip_search', methods: ['GET'])]
    public function searchTrip(Request $request, int $idCityStart, int $idCityArrival, string $dateTravel, EntityManagerInterface $em): JsonResponse
    {
        // Get the City objects for the start_id and arrive_id
        $start = $em->getRepository(City::class)->find($idCityStart);
        if (!$start) {
            throw new HttpException(404, 'Start city not found');
        }

        $arrive = $em->getRepository(City::class)->find($idCityArrival);
        if (!$arrive) {
            throw new HttpException(404, 'Arrival city not found');
        }

        // Create DateTime objects for the start and end of the travel date
        $travelDateStart = DateTime::createFromFormat('Y-m-d H:i:s', $dateTravel . ' 00:00:00');
        if (!$travelDateStart) {
            throw new HttpException(400, 'Invalid date format. Expected format is Y-m-d.');
        }

        $travelDateEnd = DateTime::createFromFormat('Y-m-d H:i:s', $dateTravel . ' 23:59:59');
        if (!$travelDateEnd) {
            throw new HttpException(400, 'Invalid date format. Expected format is Y-m-d.');
        }

        // Get the trips from the database with the given start, arrive and travel date
        $query = $em->createQuery(
            'SELECT t
        FROM App\Entity\Trip t
        WHERE t.start = :start
        AND t.arrive = :arrive
        AND t.traveldate BETWEEN :travelDateStart AND :travelDateEnd'
        )->setParameters([
            'start' => $start,
            'arrive' => $arrive,
            'travelDateStart' => $travelDateStart,
            'travelDateEnd' => $travelDateEnd,
        ]);

        $trips = $query->getResult();

        if (!$trips) {
            throw new HttpException(404, 'No trip found');
        }

        // Prepare the trips data
        $data = [];
        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'drive_id' => $trip->getDrive()->getId(),
                'start_id' => $trip->getStart()->getId(),
                'arrive_id' => $trip->getArrive()->getId(),
                'kmdistance' => $trip->getKmDistance(),
                'traveldate' => $trip->getTravelDate()->format('Y-m-d H:i:s'), // Include the full date and time
                'placesoffered' => $trip->getPlacesOffered(),
            ];
        }

        // Return the trips
        return $this->json($data);
    }

    /**
     * Get the driver of a trip
     * @param int $tripid The trip id
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listeinscriptionconducteur/{tripid}', name: 'app_trip_get_driver', methods: ['GET'])]
    public function getDriverOnTrip(int $tripid, EntityManagerInterface $em): JsonResponse
    {
        // Get the trip from the database
        $trip = $em->getRepository(Trip::class)->find($tripid);

        // If the trip is not found, return an error
        if (!$trip) {
            throw new HttpException(404, 'Trip not found');
        }

        // Get the driver of the trip
        $driver = $trip->getDrive();

        // Return the driver data
        return $this->json([
            'id' => $driver->getId(),
            'firstname' => $driver->getFirstname(),
            'name' => $driver->getName(),
            'phone' => $driver->getPhone(),
            'email' => $driver->getEmail(),
            'city' => $driver->getLive()->getName(),
            'car' => $driver->getPossess()?->getModel(),
        ]);
    }

    /**
     * Get the passengers of a trip
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @param int $studentid The student id
     * @return JsonResponse The response
     */
    #[Route('/listeinscriptionuser/{studentid}', name: 'app_trip_get_student', methods: ['GET'])]
    public function getStudentOnTrips(Request $request, EntityManagerInterface $em, int $studentid): JsonResponse
    {
        // Get the student from the database
        $student = $em->getRepository(Student::class)->find($studentid);

        // If the student is not found, return an error
        if (!$student) {
            throw new HttpException(404, 'Student not found');
        }

        // Get the trips of the student
        $trips = $student->getParticipate();

        // Create an array to store the trips data
        $data = [];

        // Loop through the trips and add the data to the array
        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'name' => $trip->getDrive()->getFirstname() . ' ' . $trip->getDrive()->getName(), // Include the driver's full name
                'drive_id' => $trip->getDrive()->getId(),
                'start_id' => $trip->getStart()->getId(),
                'arrive_id' => $trip->getArrive()->getId(),
                'kmdistance' => $trip->getKmDistance(),
                'traveldate' => $trip->getTravelDate()->format('Y-m-d H:i:s'),
                'placesoffered' => $trip->getPlacesOffered(),
            ];
        }

        // Return the trips data
        return $this->json($data);
    }

    /**
     * Get all the participations
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listeinscription', name: 'app_trip_list_participation', methods: ['GET'])]
    public function listAllParticipations(EntityManagerInterface $em): JsonResponse
    {
        // Get all the trips from the database
        $trips = $em->getRepository(Trip::class)->findAll();

        // Create an array to store the participations data
        $data = [];

        // Loop through the trips
        foreach ($trips as $trip) {
            // Get the students participating in the trip
            $students = $trip->getParticipate();

            // Loop through the students and add the data to the array
            foreach ($students as $student) {
                $data[] = [
                    'trip_id' => $trip->getId(),
                    'student_id' => $student->getId(),
                    'student_name' => $student->getFirstname() . ' ' . $student->getName(),
                    'drive_id' => $trip->getDrive()->getId(),
                    'start_id' => $trip->getStart()->getId(),
                    'arrive_id' => $trip->getArrive()->getId(),
                    'kmdistance' => $trip->getKmDistance(),
                    'traveldate' => $trip->getTravelDate()->format('Y-m-d H:i:s'),
                    'placesoffered' => $trip->getPlacesOffered(),
                ];
            }
        }

        // Return the participations data
        return $this->json($data);
    }

    /**
     * Get all the trips
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listetrajet', name: 'app_trip_list', methods: ['GET'])]
    public function listAllTrips(EntityManagerInterface $em): JsonResponse
    {
        // Get all the trips from the database
        $trips = $em->getRepository(Trip::class)->findAll();

        // Create an array to store the trips data
        $data = [];

        // Loop through the trips and add the data to the array
        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'drive_id' => $trip->getDrive()->getId(),
                'start_id' => $trip->getStart()->getId(),
                'arrive_id' => $trip->getArrive()->getId(),
                'kmdistance' => $trip->getKmDistance(),
                'traveldate' => $trip->getTravelDate()->format('Y-m-d H:i:s'),
                'placesoffered' => $trip->getPlacesOffered(),
            ];
        }

        // Return the trips data
        return $this->json($data);
    }


}
