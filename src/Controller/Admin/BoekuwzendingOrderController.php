<?php

namespace Boekuwzending\PrestaShop\Controller\Admin;

use Boekuwzending\PrestaShop\Repository\BoekuwzendingOrderRepository;
use Boekuwzending\PrestaShop\Service\BoekuwzendingClient;
use Exception;
use Order;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BoekuwzendingOrderController extends FrameworkBundleAdminController
{
    /**
     * @var BoekuwzendingClient
     */
    private $boekuwzendingClient;

    /**
     * @var BoekuwzendingOrderRepository
     */
    private $orderRepository;

    public function __construct(BoekuwzendingClient $boekuwzendingClient, BoekuwzendingOrderRepository $orderRepository)
    {
        parent::__construct();

        $this->boekuwzendingClient = $boekuwzendingClient;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param int $orderId
     * @return RedirectResponse
     */
    public function createOrder(int $orderId): RedirectResponse
    {
        PrestaShopLogger::addLog('BoekuwzendingOrderController::createOrder(): sending order to Boekuwzending', 1, null, 'Order', $orderId, true);

        try {
            $order = new Order($orderId);
            $buzOrder = $this->boekuwzendingClient->createOrder($order);
            $buzOrderId = $buzOrder->getId();

            if ($this->orderRepository->insert($orderId, $buzOrderId)) {
                PrestaShopLogger::addLog("BoekuwzendingOrderController::createOrder(): Boekuwzending order created, id: '" . $buzOrderId . "'", 1, null, 'Order', $orderId, true);
                $this->addFlash('success', $this->trans("Successfully created an order at Boekuwzending", "Boekuwzending"));
            } else {
                PrestaShopLogger::addLog("BoekuwzendingOrderController::createOrder(): could not save order", 3, null, "Order", $orderId, true);
                $this->addFlash('error', $this->trans("Failed to create an order at Boekuwzending.", "Boekuwzending"));
            }
        } catch (Exception $ex) {
            PrestaShopLogger::addLog("BoekuwzendingOrderController::createOrder(): exception: " . $ex, 3, null, 'Order', $orderId, true);
            $this->addFlash('error', $this->trans("Failed to create an order at Boekuwzending: " . $ex->getMessage(), "Boekuwzending"));
        }

        return $this->redirectToRoute('admin_orders_view', [
            'orderId' => $orderId,
        ]);
    }
}