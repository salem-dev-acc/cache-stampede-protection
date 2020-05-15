<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;


class DefaultController extends AbstractController
{
    public function withProtection()
    {
        $redisConnection = RedisAdapter::createConnection(
            'redis://1234@redis:6379'
        );


        $cache = new TagAwareAdapter(
            new RedisAdapter(
                $redisConnection
            )
        );

        $callback = function (ItemInterface $item) use ($redisConnection) {
            $item->expiresAfter(15);

            usleep(150 * 1000);

            $date = date('Y-m-d H:i:s');

            $redisConnection->lPush('list_1', $date);

            return '1111';
        };

        $value = $cache->get('item_1', $callback);

        return $this->json(compact('value'));
    }

    public function noProtection()
    {
        $redisConnection = RedisAdapter::createConnection(
            'redis://1234@redis:6379'
        );

        $value = $redisConnection->get('item_2');

        if (FALSE === $value) {
            $date = date('Y-m-d H:i:s');
            $redisConnection->lPush('list_2', $date);

            usleep(150 * 1000);
            $redisConnection->set('item_2', 'value');
            $redisConnection->setTimeout('item_2', 15);
            $value = 'value';
        }

        return $this->json(compact('value'));
    }

    public function showRedisCalls()
    {
        $redisConnection = RedisAdapter::createConnection(
            'redis://1234@redis:6379'
        );

        return $this->json([
            'list_1' => $redisConnection->lRange('list_1', 0, -1),
            'list_2' => $redisConnection->lRange('list_2', 0, -1)
        ]);
    }

    public function reset()
    {
        $redisConnection = RedisAdapter::createConnection(
            'redis://1234@redis:6379'
        );

        return $this->json([
            'deleted' => [
                'item_1' => $redisConnection->delete('item_1'),
                'list_1' => $redisConnection->delete('list_1'),
                'item_2' => $redisConnection->delete('item_2'),
                'list_2' => $redisConnection->delete('list_2'),

            ]
        ]);
    }

    public function displayResults()
    {
        $redisConnection = RedisAdapter::createConnection(
            'redis://1234@redis:6379'
        );

        $data1 = $this->getResults('list_1', $redisConnection);
        $data2 = $this->getResults('list_2', $redisConnection);

        return $this->render('base.html.twig', compact('data1', 'data2'));
    }

    private function getResults(string $list, $redisConnection)
    {
        $data = $redisConnection->lRange($list, 0, -1);

        $data = array_reverse($data);
        $step = 5;

        if (empty($data)) {
            return [
                'xData' => [],
                'yData' => [],
            ];
        }

        $min = min($data);
        $max = max($data);

        $startTime = new DateTime($min);
        $endTime = new DateTime($max);

        $intervals = [];

        while($startTime < $endTime) {

            $intervals[] = [
                'min' => $startTime->format('Y-m-d H:i:s'),
                'max' => ($startTime->modify("+$step seconds"))->format('Y-m-d H:i:s')
            ];
        }

        $set = [];

        foreach ($intervals as $key => $interval) {
            $setKey = $key * $step;
            if (!isset($set[$setKey])) {
                $set[$setKey] = 0;
            }

            foreach ($data as $date) {
                if ($date >= $interval['min'] && $date < $interval['max']) {
                    $set[$setKey]++;
                }
            }
        }

        return [
            'xData' => array_keys($set),
            'yData' => array_values($set),
        ];
    }
}
