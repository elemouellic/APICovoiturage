<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\Car;
use App\Utils\Utils;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CarController extends AbstractController
{

/**
     * Insert a new car
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/insertvoiture', name: 'app_car_insert', methods: ['POST'])]
    public function insertCar(Request $request, EntityManagerInterface $em): JsonResponse
    {

        $data = Utils::serializeRequestData($request);

        // Check if all necessary fields are present and not empty
        if (empty($data['model']) || empty($data['matriculation']) || empty($data['places']) || empty($data['brand'])) {
            throw new HttpException(400, 'Missing required fields.');
        }

        // Check if a car with the same brand and model already exists
        $existingCar = $em->getRepository(Car::class)->findOneBy(['matriculation' => $data['matriculation']]);


        if ($existingCar) {
            throw new HttpException(409, 'A car with the same brand and model already exists');
        }
        try {
            $brand = $em->getRepository(Brand::class)->findOneBy(['brand' => $data['brand']]);
            if (!$brand) {
                return $this->json([
                    'error' => 'Brand not found',
                ], 404);
            }

            // Create a new car
            $car = new Car();
            $car->setModel($data['model']);
            $car->setMatriculation($data['matriculation']);
            $car->setPlaces($data['places']);
            $car->setIdentify($brand);


            // Save the new car
            $em->persist($car);
            $em->flush();
        } catch (Exception $e) {
            throw new HttpException(400, "Error while creating the car: " . $e->getMessage());
        }
        // Return the car data
        return $this->json([
            'message' => 'Car created successfully',
            'id' => $car->getId(),
            'model' => $car->getModel(),
            'matriculation' => $car->getMatriculation(),
            'places' => $car->getPlaces(),
            'brand' => $car->getIdentify()->getBrand(),
        ]);
    }

}
