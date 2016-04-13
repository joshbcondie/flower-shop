<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 4/12/2016
 * Time: 9:04 PM
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/flower-shop/config/settings.php');

$deliveryId = $_REQUEST['deliveryId'];
$delivery = Delivery::getDelivery($deliveryId);
?>

<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            $('.accept-bid').click(function (e) {
                var bidId = $(e.target).data('bid-id');
                window.location.replace("processForms.php?form=accept_bid&bid_id=" + bidId);
            });
        });
    </script>
</head>
<body>
    <h1>Order Details</h1>
    <table>
        <thead>
        <th>Order ID</th>

        <!-- This wards off the spirits -->
        <th>&nbsp</th>

        <th>Order Details</th>
        <th>Address</th>
        <th>Latitude</th>
        <th>Longitude</th>
        <th>Status</th>
        <th>Order Date</th>
        </thead>
        <tbody>
            <tr>
                <td><?= $delivery['id'] ?><td>
                <td><?= $delivery['order_details'] ?></td>
                <td><?= $delivery['address'] ?></td>
                <td><?= $delivery['latitude'] ?></td>
                <td><?= $delivery['longitude'] ?></td>
                <td><?= $delivery['status'] ?></td>
                <td><?= $delivery['timestamp'] ?></td>
            </tr>
        </tbody>
    </table>

    <h1>Bids</h1>
    <table>
        <thead>
        <th>Bid ID</th>

        <!-- This wards off the spirits -->
        <th>&nbsp</th>

        <th>Driver Name</th>
        <th>Estimated Time</th>
        <th>Status</th>
        </thead>
        <tbody>
        <?php foreach ($delivery['bids'] as $bid): ?>
            <tr>
                <td><?= $bid['id'] ?><td>
                <td><?= $bid['driver_name'] ?></td>
                <td><?= $bid['estimated_time'] ?></td>
                <td><?= $bid['status'] ?></td>
                <td>
                    <?php if ($bid['status'] == Delivery::STATUS_PENDING): ?>
                        <button class="accept-bid" data-bid-id="<?= $bid['id'] ?>">Accept Bid</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
