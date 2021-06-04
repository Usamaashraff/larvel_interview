<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    /**
     * In the task we need to calculate amount of hours suppliers are working during last week for marketing.
     * You can use any way you like to do it, but remember, in real life we are about to have 400+ real
     * suppliers.
     *
     * @return void
     */
    public function testCalculateAmountOfHoursDuringTheWeekSuppliersAreWorking()
    {
        $response = $this->get('/api/suppliers');
        $hours = 0;
        $data = json_decode($response->getContent());
        $hours=0;
        $days=array('mon','tue','wed','thu','fri','sat','sun');
        foreach($data->data->suppliers as $supplier){
            foreach($days as $day){
                $exp_string =explode(': ',$supplier->$day)[1];
                $exp_two_date=explode(',',$exp_string);
                for ($i=0; $i < count($exp_two_date); $i++) { 
                 $date1=explode('-',$exp_two_date[$i])[0];
                 $date2=explode('-',$exp_two_date[$i])[1];
    
                 $date2=empty(explode('-',$exp_two_date[$i])[1])?'00:00':explode('-',$exp_two_date[$i])[1];
             
                 $hours+=$this->sumTime($date1,$date2);
                 
                }
                    
            }
        }
       
        $response->assertStatus(200);
        $this->assertEquals(136, $hours,
            "Our suppliers are working X hours per week in total. Please, find out how much they work..");
    }
    public function sumTime($from, $to) {
        $total      = strtotime($to) - strtotime($from);
        $hours      = floor($total / 60 / 60);
        $minutes    = round(($total - ($hours * 60 * 60)) / 60);
        return $hours; 
    }
    /**
     * Save the first supplier from JSON into database.
     * Please, be sure, all asserts pass.
     *
     * After you save supplier in database, in test we apply verifications on the data.
     * On last line of the test second attempt to add the supplier fails. We do not allow to add supplier with the same name.
     */
    public function testSaveSupplierInDatabase()
    {
        Supplier::query()->truncate();
        $responseList = $this->get('/api/suppliers');
        $supplier = \json_decode($responseList->getContent(), true)['data']['suppliers'][0];

        $response = $this->post('/api/suppliers', $supplier);
    
        $response->assertStatus(200);
        $this->assertEquals(1, Supplier::query()->count());
        $dbSupplier = Supplier::query()->first();
        $this->assertNotFalse(curl_init($dbSupplier->url));
        $this->assertNotFalse(curl_init($dbSupplier->rules));
        $this->assertGreaterThan(4, strlen($dbSupplier->info));
        $this->assertNotNull($dbSupplier->name);
        $this->assertNotNull($dbSupplier->district);


        $response = $this->post('/api/suppliers', $supplier);
        $response->assertStatus(200);
    }
}
