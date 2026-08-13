<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Models\StudentRfidCard;use PHPUnit\Framework\Attributes\DataProvider;use PHPUnit\Framework\TestCase;
class StudentRfidCardTest extends TestCase {#[DataProvider('uids')]public function test_uid_is_normalized_to_one_canonical_format(string $raw,string $expected):void{$this->assertSame($expected,StudentRfidCard::normalizeUid($raw));}public static function uids():array{return [['04:A3:1f:B2:91','04A31FB291'],['04-a3-1F-b2-91','04A31FB291'],[' 04A31FB291 ','04A31FB291']];}public function test_uid_is_masked_for_display():void{$card=new StudentRfidCard(['uid'=>'04A31FB291']);$this->assertSame('04:A3:••:••:91',$card->maskedUid());}}
