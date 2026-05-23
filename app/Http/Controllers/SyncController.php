<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function processBatch(Request $request, InvoiceService $invoiceService)
    {
        $payload = $request->validate([
            'operations' => ['required', 'array'],
            'operations.*.queue_id' => ['nullable'],
            'operations.*.local_id' => ['required', 'string'],
            'operations.*.action' => ['required', 'string'],
            'operations.*.entity_type' => ['required', 'string'],
            'operations.*.payload' => ['required', 'array'],
        ]);

        $results = [];
        $localMap = [];

        $priority = [
            'create_customer' => 100,
            'update_customer' => 200,
            'delete_customer' => 300,
            'create_invoice' => 400,
            'update_invoice' => 500,
            'delete_invoice' => 600,
        ];

        usort($payload['operations'], function ($a, $b) use ($priority) {
            return ($priority[$a['action']] ?? 999) <=> ($priority[$b['action']] ?? 999);
        });

        $repairServerId = function ($value) use (&$localMap) {
            if (!$value) {
                return null;
            }

            if (is_numeric($value)) {
                return $value;
            }

            return $localMap[$value] ?? null;
        };

        foreach ($payload['operations'] as $operation) {
            try {
                switch ($operation['action']) {
                    case 'create_customer':
                        $customer = Customer::create($operation['payload']);
                        $localMap[$operation['local_id']] = $customer->id;
                        $results[] = [
                            'queue_id' => $operation['queue_id'] ?? null,
                            'local_id' => $operation['local_id'],
                            'entity_type' => 'customer',
                            'success' => true,
                            'server_id' => $customer->id,
                        ];
                        break;

                    case 'update_customer':
                        $serverId = $repairServerId($operation['payload']['server_id'] ?? null);
                        $serverId = $serverId ?: $repairServerId($operation['local_id']);
                        $customer = Customer::findOrFail($serverId);
                        $customer->update($operation['payload']);
                        $results[] = [
                            'queue_id' => $operation['queue_id'] ?? null,
                            'local_id' => $operation['local_id'],
                            'entity_type' => 'customer',
                            'success' => true,
                            'server_id' => $customer->id,
                        ];
                        break;

                    case 'delete_customer':
                        $serverId = $repairServerId($operation['payload']['server_id'] ?? null);
                        if ($serverId) {
                            $customer = Customer::find($serverId);
                            if ($customer) {
                                $customer->delete();
                            }
                        }
                        $results[] = [
                            'queue_id' => $operation['queue_id'] ?? null,
                            'local_id' => $operation['local_id'],
                            'entity_type' => 'customer',
                            'success' => true,
                        ];
                        break;

                    case 'create_invoice':
                        $payloadData = $operation['payload'];
                        if (!empty($payloadData['customer_id'])) {
                            $payloadData['customer_id'] = $repairServerId($payloadData['customer_id']) ?: $payloadData['customer_id'];
                        }
                        $invoice = $invoiceService->create($payloadData);
                        $results[] = [
                            'queue_id' => $operation['queue_id'] ?? null,
                            'local_id' => $operation['local_id'],
                            'entity_type' => 'invoice',
                            'success' => true,
                            'server_id' => $invoice->id,
                            'server_invoice_no' => $invoice->invoice_no,
                        ];
                        break;

                    case 'update_invoice':
                        $serverId = $repairServerId($operation['payload']['server_id'] ?? null);
                        if (!$serverId) {
                            throw new \RuntimeException('Missing invoice server_id');
                        }
                        $invoiceModel = Invoice::findOrFail($serverId);
                        $payloadData = $operation['payload'];
                        if (!empty($payloadData['customer_id'])) {
                            $payloadData['customer_id'] = $repairServerId($payloadData['customer_id']) ?: $payloadData['customer_id'];
                        }
                        $invoice = $invoiceService->update($invoiceModel, $payloadData);
                        $results[] = [
                            'queue_id' => $operation['queue_id'] ?? null,
                            'local_id' => $operation['local_id'],
                            'entity_type' => 'invoice',
                            'success' => true,
                            'server_id' => $invoice->id,
                            'server_invoice_no' => $invoice->invoice_no,
                        ];
                        break;

                    case 'delete_invoice':
                        $serverId = $repairServerId($operation['payload']['server_id'] ?? null);
                        if ($serverId) {
                            $invoiceService->delete($serverId);
                        }
                        $results[] = [
                            'queue_id' => $operation['queue_id'] ?? null,
                            'local_id' => $operation['local_id'],
                            'entity_type' => 'invoice',
                            'success' => true,
                        ];
                        break;

                    default:
                        throw new \RuntimeException('Unsupported sync action: ' . $operation['action']);
                }
            } catch (\Throwable $exception) {
                Log::warning('Swap sync failed', ['error' => $exception->getMessage(), 'operation' => $operation]);
                $results[] = [
                    'queue_id' => $operation['queue_id'] ?? null,
                    'local_id' => $operation['local_id'],
                    'entity_type' => $operation['entity_type'] ?? 'unknown',
                    'success' => false,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}
