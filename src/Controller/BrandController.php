<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Utils\Utils;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class BrandController
 * @package App\Controller
 */
#[Route('/api')]
class BrandController extends AbstractController
{

    /**
     * Insert a new brand
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/insertmarque', name: 'app_brand_insert', methods: ['POST'])]
    public function insertBrand(Request $request, EntityManagerInterface $em): JsonResponse
    {

        $data = Utils::serializeRequestData($request);

        // Check if all necessary fields are present and not empty
        if (empty($data['brand'])) {
            throw new HttpException(400, 'Missing required fields.');
        }

        // Check if a brand with the same name already exists
        $existingBrand = $em->getRepository(Brand::class)->findOneBy(['brand' => $data['brand']]);

        if ($existingBrand) {
            throw new HttpException(409, 'A brand with the same name already exists');
        }

        try {
            // Create a new brand
            $brand = new Brand();
            $brand->setBrand($data['brand']);

            // Save the new brand
            $em->persist($brand);
            $em->flush();


        } catch (Exception $e) {
            throw new HttpException(400, "Error while creating the brand: " . $e->getMessage());
        }
        // Return the brand data
        return $this->json([
            'message' => 'Brand created successfully',
            'id' => $brand->getId(),
            'brand' => $brand->getBrand(),
        ]);
    }

    /**
     * Delete a brand
     * @param int $id The brand id
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/deletemarque/{id}', name: 'app_brand_delete', methods: ['DELETE'])]
    public function deleteBrand(int $id, EntityManagerInterface $em): JsonResponse
    {
        $brand = $em->getRepository(Brand::class)->find($id);
        if (!$brand) {
            throw new HttpException(404, 'No brand found for id ' . $id);
        }
        try {
            $em->remove($brand);
            $em->flush();
        } catch (Exception $e) {
            throw new HttpException(400, "Error while deleting the brand");
        }
        return $this->json([
            'message' => 'Brand deleted successfully',
        ]);
    }

    /**
     * List all the brands
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listemarque', name: 'app_brand_list', methods: ['GET'])]
    public function listAllBrands(EntityManagerInterface $em): JsonResponse
    {
        // Get all the brands from the database ordered by id
        $brands = $em->getRepository(Brand::class)->findBy([], ['id' => 'ASC']);

        $data = [];
        foreach ($brands as $brand) {
            $data[] = [
                'id' => $brand->getId(),
                'brand' => $brand->getBrand(),
            ];
        }
        return $this->json($data);
    }
}
