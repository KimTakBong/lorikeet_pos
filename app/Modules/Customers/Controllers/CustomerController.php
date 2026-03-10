<?php

namespace App\Modules\Customers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Services\CustomerService;
use App\Rules\ValidPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'tag_id' => $request->query('tag_id'),
        ];

        $customers = $this->customerService->getCustomers(array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->getCustomerById($id);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', new ValidPhoneNumber()],
            'email' => 'nullable|email|max:150',
            'birthday' => 'nullable|date',
        ]);

        $customer = $this->customerService->createCustomer($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'phone' => ['sometimes', 'required', 'string', new ValidPhoneNumber()],
            'email' => 'nullable|email|max:150',
            'birthday' => 'nullable|date',
        ]);

        $customer = $this->customerService->updateCustomer($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->customerService->deleteCustomer($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }

    public function tags(): JsonResponse
    {
        $tags = $this->customerService->getTags();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    public function stats(int $id): JsonResponse
    {
        $stats = $this->customerService->getCustomerStats($id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
