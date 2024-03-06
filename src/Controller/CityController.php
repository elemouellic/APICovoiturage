<?php

namespace App\Controller;

use App\Entity\City;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CityController extends AbstractController
{

    /**
     * Insert a new city
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/insertville', name: 'app_city_insert', methods: ['POST'])]
    public function insertCity(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = Utils::serializeRequestData($request);

        // Check if all necessary fields are present and not empty
        if (empty($data['name']) || empty($data['zipcode'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Check if a city with the same name and zip code already exists
        $existingCity = $em->getRepository(City::class)->findOneBy([
            'name' => $data['name'],
            'zipcode' => $data['zipcode'],
        ]);

        if ($existingCity) {
            throw new HttpException(409, 'A city with the same name and zip code already exists');
        }

        try {
            // Create a new city
            $city = new City();
            $city->setName($data['name']);
            $city->setZipcode($data['zipcode']);

            // Save the new city
            $em->persist($city);
            $em->flush();


        } catch (Exception $e) {
            throw new HttpException(400, "Error while creating the city ");
        }
        // Return the city data
        return $this->json([
            'message' => 'City created successfully',
            'id' => $city->getId(),
            'name' => $city->getName(),
            'zipcode' => $city->getZipcode(),
        ]);
    }

    /**
     * Delete a city
     * @param Request $request The request object
     * @param int $id The city id
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/deleteville/{id}', name: 'app_city_delete', methods: ['DELETE'])]
    public function deleteCity(Request $request, $id, EntityManagerInterface $em): JsonResponse
    {

        // Get the city from the database
        $city = $em->getRepository(City::class)->find($id);
        if (!$city) {
            throw new HttpException(404, 'City not found');
        }

        try {
            $em->remove($city);
            $em->flush();

            return $this->json([
                'message' => 'City deleted successfully',
            ]);
        } catch (Exception $e) {
            throw new HttpException(400, "Error while deleting the city");
        }
    }

    /**
     * List all the cities
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listeville', name: 'app_city_list', methods: ['GET'])]
    public function listAllCities(EntityManagerInterface $em): JsonResponse
    {
        // Get all the cities from the database
        $cities = $em->getRepository(City::class)->findAll();

        // Initialize the array that will store the formatted data
        $data = [];

        // Loop through the cities and add the data to the array
        foreach ($cities as $city) {
            $data[] = [
                'id' => $city->getId(),
                'name' => $city->getName(),
                'zipcode' => $city->getZipcode(),
            ];
        }

        return $this->json($data);
    }

    /**
     * List all the zip codes
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listecodepostal', name: 'app_zipcode_list', methods: ['GET'])]
    public function listAllZipCodes(EntityManagerInterface $em): JsonResponse
    {
        // Get all the zip codes from the database
        $zipcodes = $em->getRepository(City::class)->findAll();

        // Initialize the array that will store the formatted data
        $data = [];

        // Loop through the zip codes and add the data to the array
        foreach ($zipcodes as $zipcode) {
            $data[] = [
                'id' => $zipcode->getId(),
                'zipcode' => $zipcode->getZipcode(),
            ];
        }

        return $this->json($data);
    }

}
