<?php /** @noinspection PhpMultipleClassDeclarationsInspection - there is only one Db class at runtime. */

namespace Boekuwzending\PrestaShop\Repository;

use Boekuwzending\PrestaShop\ViewModels\BoekuwzendingOrder;
use DateTime;
use DateTimeZone;
use Db;
use DbQuery;
use Exception;
use PrestaShopDatabaseException;

class BoekuwzendingOrderRepository
{
    /**
     * @var Db
     */
    private $db;

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @returns
     */
    public function insert(int $prestaOrderId, string $buzOrderId): mixed
    {
        $now = new DateTime();
        /** @noinspection PhpCastIsUnnecessaryInspection, UnnecessaryCastingInspection - PrestaShop requires it */
        if (!$this->db->insert('boekuwzending_order', array(
            'id_order' => (int)$prestaOrderId,
            'boekuwzending_external_order_id' => pSQL($buzOrderId),
            'created_datetime' => pSQL($now->format("Y-m-d H:i:s"))
        )))
        {
            return $this->db->getMsgError();
        }

        return true;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws Exception
     */
    public function findByOrderId(int $orderId): array
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('boekuwzending_order', 'b');
        $query->where('b.id_order = ' . $orderId);
        $query->orderBy('created_datetime DESC');

        $orders = $this->db->query($query);

        $buzOrders = [];

        foreach ($orders as $order) {
            $buzOrder = new BoekuwzendingOrder();

            // TODO: error handling parsing, or get it from PDO as DateTime already
            $buzOrder->setCreated(new DateTime($order["created_datetime"]));
            $buzOrder->setBoekuwzendingId($order["boekuwzending_external_order_id"]);

            $buzOrders[] = $buzOrder;
        }

        return $buzOrders;
    }
}