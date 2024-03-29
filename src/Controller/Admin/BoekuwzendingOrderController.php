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

            if (($result = $this->orderRepository->insert($orderId, $buzOrderId)) === true) {
                PrestaShopLogger::addLog("BoekuwzendingOrderController::createOrder(): Boekuwzending order created, id: '" . $buzOrderId . "'", 1, null, 'Order', $orderId, true);
                $this->addFlash('success', $this->trans("Successfully created an order at Boekuwzending.", "Modules.Boekuwzending.Boekuwzendingordercontroller"));
            } else {
                PrestaShopLogger::addLog("BoekuwzendingOrderController::createOrder(): could not save order: " . $result, 3, null, "Order", $orderId, true);
                $this->addFlash('error', $this->trans("Failed to create an order at Boekuwzending: %error%", "Modules.Boekuwzending.Boekuwzendingordercontroller", [ '%error%' => $result ]));
            }
        } catch (Exception $ex) {
            PrestaShopLogger::addLog("BoekuwzendingOrderController::createOrder(): exception: " . $ex, 3, null, 'Order', $orderId, true);
            $userError = $this->trans("Failed to create an order at Boekuwzending: %exType%: %exMessage%", "Modules.Boekuwzending.Boekuwzendingordercontroller", [ '%exType%' => get_class($ex), '%exMessage%' => $ex->getMessage() ]);
            $this->addFlash('error', $userError);
        }

        return $this->redirectToRoute('admin_orders_view', [
            'orderId' => $orderId,
        ]);
    }
}