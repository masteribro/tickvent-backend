<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    public function test_create_event_endpoint() {

        $response = $this->postJson("/",[
                    "name" => "Web Feast",
                    "description" => "This event is about meeting other software engineer, and collaborating more and get more connection",
                    "start_date" => Carbon::now()->addWeek()->format("Y-m-d"), // There is a validation of date
                    "start_time" => "10:00:00",
                    "type" => "physical",
                    "location" => "No 40, Gosa Bridge, Confirmed",
                    "reminders" => [Carbon::now()->addDays(1), Carbon::now()->addDays(2)],
                    "tags" => ["web","AI", "web3", "software development"],
                    "images" => ["ehferuifheihferiferivbrg vn ene rnenrferjbhrhfbhrbfrhfbrbf   f rrjfjrfr frjf rfjr fjr frjf rfjr fr fr fr frfjreke rke re rjk kjw jkw jer frhfr jrkejn refjkrfr fjrf erjkwue;wwr tontngmt,md v jruf  trmt fgfgrtv tyb tyyrv  fjkfkjvrv rtjtr trjr rkjrt ;vbfjkfnvdjkndjbdjbdjbffvbriovnreiovndndkcndfjkfdvjfbvfnvbfvfvvfvbfvhfbvfhfbvfvfvfvfhvfbvfhfbhjejekdjedmcnecjencekjvkrjvntrjbhtbbem,s fhbhebvrvbrirtruwrty;rtpwfdj snjksdnenrerkngjnjrnutejwjrfn,dmfmfvf vnvtrjtrtknrtrjuuuyooeppwqwxmdsmdc nc v rjktvtrjkvtrkjvtttttttgnbfsmds.d,.fnnrgkjjkrkrdfjnjfvfnvfvfvfvffffvnvnfvbdsm,x,sm,dksldklllsdkleekllleldlleldedelllerlllelrlrllrllrmmremcmemcmecmemcmemmma asqwqlwlkkekfkfjrjejfjrfjeferkffmbbh erhjjbehrfbejer n ekw kekjerejkrer  reejfre rrj rjrj jejeklwkf er errjfjre frejjer jerr fref ewl le  er frelferf er ferf ll er fl er"] 
        ]);

        $response->assertStatus(200);

    }
}
