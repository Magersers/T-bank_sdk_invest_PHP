<?php
   class phpBotInvestCastom
   {
     public $accessToken;
     public $accountId;
     // BBG004S68473 - ИНТЕР РАО
     // BBG004S683W7 - АЭРОФЛОТ
     // BBG004730RP0 - Газпром
     // BBG004S68598 - Мечел
     // TCS00A1002V2 - ЕвроТранс
     // TCS00A106YF0 - Вк
     // BBG000R04X57 - софкомфлот
     public function __construct($token,$id) 
     {
       $this -> accessToken = $token;
       $this -> accountId = $id;
     }
     //Получение портфеля
     public function PortfelInfo()
     {
        $url = 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.OperationsService/GetPortfolio';
        $accessToken =  $this -> accessToken;
        
        $data = array(
            'accountId' => $this -> accountId,
            'currency' => 'RUB'
        );
        
        $payload = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response === false) 
        {
            return 'Error: ';
        } 
        else 
        {
            return $response;
        }
        
        curl_close($ch);
        
        
     }
     // создание заявки
     public function BuyAshare($share,$lot,$prise,$order,$orderType,$OrderDirection)
     {
       $prise = explode(".",$prise);
   
       if($prise[1] == null || $prise[1] == '00')
       {
         $prise[1] == 000000000;
       }
       $count = strlen($prise[1]);
       if($count == null)
         $nuli = '000000000';
       if($count == 1)
         $nuli = '00000000';
       if($count == 2)
         $nuli = '0000000';
       if($count == 3)
         $nuli = '000000';
         $curl = curl_init();
         curl_setopt_array($curl, array(
           CURLOPT_URL => 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.OrdersService/PostOrder',
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 0,
           CURLOPT_FOLLOWLOCATION => true,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_HTTPHEADER => array(
             'accept: application/json',
             "Authorization: Bearer {$this ->accessToken }",
             'Content-Type: application/json'
           ),
           CURLOPT_POSTFIELDS => "{
             'figi': '$share',
             'quantity': '$lot',
             'price': {
               'nano': {$prise[1]}{$nuli},
               'units': '{$prise[0]}'
             },
             'direction': '{$OrderDirection}',
             'accountId': '{$this -> accountId}',
             'orderType': '{$orderType}',
             'orderId': '{$order}',
             'instrumentId': 'string'
           }"
         ));
       $response = curl_exec($curl);
       $mas = json_decode($response,true);
       if($mas['message'] != null)
       {
         return $mas;
       }
       $masResult['priсe'] = $this -> convertNano($mas['initialOrderPrice']['units'],$mas['initialOrderPrice']['nano']);
       $masResult['orderId'] = $mas['orderId'];
       $masResult['status'] = $mas['executionReportStatus'];
       $masResult['share'] = $mas["figi"];
       return $masResult;
     }
     // статус ореда
     public function StatusOrder($order)
     {
       $ch = curl_init();
   
       curl_setopt($ch, CURLOPT_URL, 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.OrdersService/GetOrderState');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"accountId\":\"{$this -> accountId}\",\"orderId\":\"{$order}\",\"priceType\":\"PRICE_TYPE_CURRENCY\"}");
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
           'Accept: application/json',
           "Authorization: Bearer {$this -> accessToken }",
           'Content-Type: application/json'
       ));
       
   
         $result = curl_exec($ch);
         if (curl_errno($ch)) 
         {
            return 'Error:' . curl_error($ch);
         }
         curl_close($ch);
         $result = json_decode($result,true);
   
         if($result['executionReportStatus'] == "EXECUTION_REPORT_STATUS_CANCELLED")
           $status = "Заявка закрыта пользователем";
   
         elseif($result['executionReportStatus'] == "EXECUTION_REPORT_STATUS_FILL")
           $status = "Заявка исполнена";
   
         elseif($result['executionReportStatus'] == "EXECUTION_REPORT_STATUS_REJECTED")
           $status = "Заявка отклонена биржей";
   
         elseif($result['executionReportStatus'] == "EXECUTION_REPORT_STATUS_NEW")
           $status = "Заявка создана";
   
         elseif($result['executionReportStatus'] == "EXECUTION_REPORT_STATUS_PARTIALLYFILL")
           $status = "Заявка частично исполнена";
         else
           return 'ошибка';
         
         return $status;
     }
     // получение цены акции
     public function PriseShare($share)
     {
       $url = 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.MarketDataService/GetLastPrices';
       $data = array(
           "figi" => ["{$share}"],
           "instrumentId" => ["string"]
       );
   
       $data_string = json_encode($data);
   
       $ch = curl_init($url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   
       $headers = array(
           'accept: application/json',
           "Authorization: Bearer {$this -> accessToken}",
           'Content-Type: application/json',
       );
   
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   
       $response = curl_exec($ch);
   
       if ($response === false) 
       {
         $masItog =  'Ошибка curl: ' . curl_error($ch);
       } 
       else 
       {
           $result = json_decode($response,true);
           $masItog['prise'] = $this -> convertNano($result['lastPrices'][0]['price']['units'],$result['lastPrices'][0]['price']['nano']);
           $masItog['share'] = $result['lastPrices'][0]['figi'];
       }
   
       curl_close($ch);
       return $masItog;
     }
     // Отмена заявки 
     public function StopOrder($order)
     {
         $url = 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.OrdersService/CancelOrder';
         $data = array
         (
             "accountId" => "{$this -> accountId}",
             "orderId" => "{$order}"
         );
   
         $data_string = json_encode($data);
   
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $headers = array(
             'accept: application/json',
             "Authorization: Bearer {$this -> accessToken }",
             'Content-Type: application/json',
         );
         
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   
         $response = curl_exec($ch);
   
         if ($response === false) 
         {
             return 'Ошибка curl: ' . curl_error($ch);
         } 
         else 
         {
             return $response;
         }
   
         curl_close($ch);
     }
     // Получения стакана для анализа
     public function StakanInfo($share, $depth)
     {
       $curl = curl_init();
   
       curl_setopt_array($curl, array(
         CURLOPT_URL => 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.MarketDataService/GetOrderBook',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_HTTPHEADER => array(
           'accept: application/json',
           "Authorization: Bearer {$this -> accessToken} ",
           'Content-Type: application/json'
         ),
         CURLOPT_POSTFIELDS => "{
         'figi': '$share',
         'depth': $depth,
         'instrumentId': 'string'
       }",
       ));
       $response = curl_exec($curl);
       $response = json_decode($response,true);
       $masStak = array();
       $i = 0;
       while($response['depth'] > $i)
       {
         $masStak['prise'][$i] = $this -> convertNano($response['bids'][$i]['price']['units'],$response['bids'][$i]['price']['nano']);
         $masStak['quantity'][$i] =  $response['bids'][$i]['quantity'];
         $masStak['priseSell'][$i] = $this -> convertNano($response['asks'][$i]['price']['units'],$response['asks'][$i]['price']['nano']);
         $masStak['quantitySell'][$i] =  $response['asks'][$i]['quantity'];
         // echo '<br>'.'Цена'.$masStak['prise'][$i].' Заявок на покупку  '.$masStak['quantity'][$i];
         // echo '<br>'.'Цена'.$masStak['priseSell'][$i].' Заявок на продажу  '.$masStak['quantitySell'][$i];
         $i++;
       }
       curl_close($curl);
       return $masStak;                                           
     }                                                        
     // конвертит цену в нормальную
     public function convertNano($nenano,$nano)             
     {                                                    
       $count = strlen($nano);                          
        if($count ==7)                                
       {                                           
         $nano = '00'. rtrim($nano, 0);
         $prise = $nenano.'.'.$nano;
         return $prise;
       }                                                                                                                  
       else if($count == 8)
       {
         $nano = '0'. rtrim($nano, 0);
         $prise = $nenano.'.'.$nano; 
         return $prise;
       }
       else if($count == 9)
       {
         $nano =  rtrim($nano, 0);
         $prise = $nenano.'.'.$nano;
         return $prise;

       }
       else  if($count == 1)
         $prise = $nenano.'.'.'00';
         return $prise;
     }
     // просчёт вероятности роста или падения
     public function calculateProbability($data) 
     {
         
         $buyTotal = array_sum($data['quantity']);
         $sellTotal = array_sum($data['quantitySell']);
         $buyProbability = $buyTotal / ($buyTotal + $sellTotal) * 100;
         $sellProbability = $sellTotal / ($buyTotal + $sellTotal) * 100;
         return ['buyProbability' => $buyProbability, 'sellProbability' => $sellProbability];
     }
     // Выставление стоп заявки 
     public function StopOrderSell($share,$prise,$col)
     {
       $prise = explode(".",$prise);
   
       if($prise[1] == '00')
       {
         $prise[1] == '000000000';
       }
       $count = strlen($prise[1]);
       if($count == null)
         $nuli = '000000000';
       if($count == 1)
         $nuli = '00000000';
       if($count == 2)
         $nuli = '0000000';
       if($count == 3)
         $nuli = '000000';
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.StopOrdersService/PostStopOrder');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, "{
         'figi': '{$share}',
         'quantity': {$col},
         'price': {
           'nano': 0,
           'units': 4 
         },
         'stopPrice': {
           'units': {$prise[0]},
           'nano': {$prise[1]}{$nuli}
         },
         'direction': 'STOP_ORDER_DIRECTION_SELL',
         'accountId': '{$this ->accountId }',
         'expirationType': 'STOP_ORDER_EXPIRATION_TYPE_GOOD_TILL_CANCEL',
         'stopOrderType': 'STOP_ORDER_TYPE_STOP_LIMIT'
       }");
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
         'accept: application/json',
         "Authorization: Bearer {$this -> accessToken}",
         'Content-Type: application/json'
       ));
       
       $result = curl_exec($ch);
       if (curl_errno($ch)) 
       {
           echo 'Error:' . curl_error($ch);
       }
       curl_close ($ch);
       return $result;
     }
     // Закрытие стоп заявки
     public function StopOrderStop($orderId)
     {
       $ch = curl_init();

       curl_setopt($ch, CURLOPT_URL, 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.StopOrdersService/CancelStopOrder');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"accountId\": \"{$this -> accountId}\",\"stopOrderId\": \"{$orderId}\"}");

       $headers = array();
       $headers[] = 'Accept: application/json';
       $headers[] = "Authorization: Bearer {$this -> accessToken} ";
       $headers[] = 'Content-Type: application/json';
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

       $result = curl_exec($ch);
       if (curl_errno($ch)) 
       {
           echo 'Error:' . curl_error($ch);
       }
       curl_close ($ch);
       return $result;
     }
     // Получение макс-мин из массива для анализа
     public function MaxMin($array) 
     {
       
       if (empty($array)) 
       {
           return;
       }
   
       $min = $array[0];
   
       foreach ($array as $value) 
       {
           if ($value > $max) 
           {
               $max = $value;
           }
           if ($value < $min) 
           {
               $min = $value;
           }
       }
       $msx[0] = $max;
       $msx[1] = $min;
       return $msx;
     }
     // Время
     public function getCurrentTime() 
     {
       $hour = date('H');
       $minute = date('i');
       return str_pad($hour, 2, '0', STR_PAD_LEFT) . str_pad($minute, 2, '0', STR_PAD_LEFT);
     }
     // Вывод имени и ссылки на картинку акции по figi(Делает большую нагрузку, но удобно)
     public function shareNameFigi($figi)
     {
      $url = 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.InstrumentsService/ShareBy';
      $data = array(
          "idType" => "INSTRUMENT_ID_TYPE_FIGI",
          "classCode" => "string",
          "id" => $figi
      );
      
      $payload = json_encode($data);
      
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'accept: application/json',
          "Authorization: Bearer {$this -> accessToken }",
          'Content-Type: application/json'
      ));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      
      $response = curl_exec($ch);
      curl_close($ch);
      $response = json_decode($response,true);
      $mas['name'] =  $response['instrument']['name'] ; 
      $mas['img'] = $response['instrument']['brand']['logoName'];
      $mas['img'] = 'https://invest-brands.cdn-tinkoff.ru/'.preg_replace('/.png/','x160.png',$mas['img']);
      return   $mas;
     }
     // Получение статуса маржи
     public function MargeStatus()
     {
      $url = 'https://invest-public-api.tinkoff.ru/rest/tinkoff.public.invest.api.contract.v1.UsersService/GetMarginAttributes';
      $token = "Bearer  {$this -> accessToken}";
      $data = json_encode(array(
          'accountId' => $this -> accountId
      ));
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'accept: application/json',
          'Authorization: ' . $token,
          'Content-Type: application/json',
      ));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      
      $response = json_decode(curl_exec($ch),true);
      curl_close($ch);
      if ($response === false) {
          return 'Ошибка cURL: ' . curl_error($ch);
      } else {
          return $response;
      }
      
     }
   
}
?>