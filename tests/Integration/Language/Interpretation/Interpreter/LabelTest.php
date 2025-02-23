<?php

namespace Tests\Polygen\Integration\Language\Interpretation\Interpreter;

use Polygen\Language\Document;
use Polygen\Language\Interpretation\Context;
use Polygen\Polygen;
use Tests\StreamUtils;
use Tests\TestCase;

/**
 * A few tests involving label selection.
 */
class LabelTest extends TestCase
{
    use StreamUtils;

    /**
     * This example was lifted directly from the Polygen documentation (section 2.0.5.1 "Etichette e selezione")
     * @test
     * @param int $seed
     * @dataProvider provider_selection_feed
     */
    public function it_correctly_selects_labels($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
            S ::= Nome.S mangia Nome.P | (Nome mangiano Nome).P ;
            Nome ::= (S: il | P: i) (lup | gatt) ^ (S: o | P: i) ;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));

        $acceptable = [
            'il lupo mangia i lupi',
            'il lupo mangia i gatti',
            'il gatto mangia i lupi',
            'il gatto mangia i gatti',
            'i lupi mangiano i lupi',
            'i lupi mangiano i gatti',
            'i gatti mangiano i lupi',
            'i gatti mangiano i gatti',
        ];

        $this->assertContains($generated, $acceptable);
    }

    /**
     * @return \string[][]
     */
    static function provider_selection_feed()
    {
        // At the time of writing, most of these numbers produced invalid results.
        return [
            ['0'],
            ['1'],
            ['2'],
            ['3'],
            ['4'],
            ['5'],
            ['6'],
            ['7'],
            ['8'],
            ['9'],
        ];
    }

    /**
     * @test
     */
    public function non_terminating_symbols_cant_reset_the_label_selections()
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                    I ::= test;
                    
                    S ::= A.a;
                    A ::= a: a B and. C;
                    B ::= a: b | c: nope;
                    (*
                      This test also triggers the case where all productions have been excluded from generation, since
                      there are no productions suitable for generation in C.
                    *)
                    C ::= g: c;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START));

        $this->assertEquals('a b and', $generated);
    }

    /**
     * This example was lifted directly from the Polygen documentation (section 2.0.5.1 "Etichette e selezione") with
     * just a minor adjustment because apparently the example in the documentation was not valid due to an extra closed
     * parenthesis. :)
     *
     * @test
     * @dataProvider provider_label_propagation
     */
    public function it_propagates_label_selections($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                S ::= (Ogg.M | Ogg.F).S | (Ogg.M | Ogg.F).P ;
                
                Ogg ::= M: ((Art Sost).il | (Art Sost).lo)
                     |  F: Art Sost ;
                
                Art ::= M: (il: (S: il | P: i) | lo: (S: lo | P: gli))
                     |  F: (S: la | P: le) ;
                
                Sost ::= M: ( il: (lup ^ Decl.2 | can ^ Decl.3) (* ) *)
                            | lo: (gnom ^ Decl.2 | zabaion ^ Decl.3))
                      |  F: pecor ^ Decl.1 ;
                
                Decl ::= 1: (S: a | P: e) | 2: (S: o | P: i) | 3: (S: e | P: i) ;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));

        $acceptable = [
            'il lupo',
            'il cane',
            'lo gnomo',
            'lo zabaione',
            'la pecora',
            'i lupi',
            'i cani',
            'gli gnomi',
            'gli zabaioni',
            'le pecore',
        ];

        $this->assertContains($generated, $acceptable);
    }

    static function provider_label_propagation()
    {
        // At the time of writing, these seeds generated all possible productions.
        return [
            ['332'], // lo gnomo
            ['19'], // gli gnomi
            ['10'], // la pecora
            ['1'], // le pecore
            ['2'], // il lupo
            ['15'], // i lupi
            ['4'], // il cane
            ['13'], // i cani
            ['30'], // lo zabaione
            ['3'], // gli zabaioni
        ];
    }

    /**
     * This example was lifted directly from the Polygen documentation (section 2.0.5.1 "Etichette e selezione")
     * @test
     */
    public function it_supports_multiple_label_selection_groups()
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                S ::= Ogg.(M|F).(S|P) ;
                
                Ogg ::= M: ((Art Sost).il | (Art Sost).lo)
                     |  F: Art Sost ;
                
                Art ::= M: (il: (S: il | P: i) | lo: (S: lo | P: gli))
                     |  F: (S: la | P: le) ;
                
                Sost ::= M: ( il: (lup ^ Decl.2 | can ^ Decl.3) (* ) *)
                            | lo: (gnom ^ Decl.2 | zabaion ^ Decl.3))
                      |  F: pecor ^ Decl.1 ;
                
                Decl ::= 1: (S: a | P: e) | 2: (S: o | P: i) | 3: (S: e | P: i) ;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START));

        $acceptable = [
            'il lupo',
            'il cane',
            'lo gnomo',
            'lo zabaione',
            'la pecora',
            'i lupi',
            'i cani',
            'gli gnomi',
            'gli zabaioni',
            'le pecore',
        ];

        $this->assertContains($generated, $acceptable, $context->getSeed());
    }

    /**
     * Even though this is not advertised in the documentation, it is possible to enforce multiple label selections
     * by consecutively adding a dot and then a label name, concatenating the selections.
     * @test
     */
    public function it_supports_multiple_consecutive_label_selections()
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                S ::= Ogg.F.P ;
                
                Ogg ::= M: ((Art Sost).il | (Art Sost).lo)
                     |  F: Art Sost ;
                
                Art ::= M: (il: (S: il | P: i) | lo: (S: lo | P: gli))
                     |  F: (S: la | P: le) ;
                
                Sost ::= M: ( il: (lup ^ Decl.2 | can ^ Decl.3) (* ) *)
                            | lo: (gnom ^ Decl.2 | zabaion ^ Decl.3))
                      |  F: pecor ^ Decl.1 ;
                
                Decl ::= 1: (S: a | P: e) | 2: (S: o | P: i) | 3: (S: e | P: i) ;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START));

        $this->assertEquals($generated, 'le pecore', $context->getSeed());
    }

    /**
     * @test
     * @dataProvider provider_label_selection_processing_order
     * This test verifies that since the dot is the rightmost character in the label selection, and being processed
     * first, the one and b label selections are still applied, thus only generating 'b'.
     */
    public function it_processes_label_selections_from_right_to_left_1($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                S ::= Generate.one.b.;
                Generate ::= a: A | b: B;
                A ::= one: a | two: aa;
                B ::= one: b | two: bb;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));

        $this->assertEquals($generated, 'b', $context->getSeed());
    }

    static function provider_label_selection_processing_order()
    {
        return [
            ['1'],
            ['2'],
            ['3'],
            ['4'],
            ['5'],
            ['6'],
            ['7'],
            ['8'],
            ['9'],
            ['0'],
        ];
    }

    /**
     * @test
     * @dataProvider provider_left_to_right_selection_2
     * This test checks that the labels selected after the double dot are ignored.
     * @param string $seed
     */
    public function it_processes_label_selections_from_right_to_left_2($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                S ::= Generate.one.a..two.b Generate.two.a..one.b Generate.one.b..two.a Generate.two.b..one.a;
                Generate ::= a: A | b: B;
                A ::= one: a | two: aa;
                B ::= one: b | two: bb;
GRAMMAR
            ),
            Document::START
        );
        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));


        $this->assertEquals($generated, 'a aa b bb');
    }

    static function provider_left_to_right_selection_2()
    {
        return [
            ['1'],
            ['2'],
            ['3'],
            ['4'],
            ['5'],
            ['6'],
            ['7'],
            ['8'],
            ['9'],
            ['0'],
        ];
    }
    /**
     * This example was lifted directly from the Polygen documentation (section 2.0.5.1 "Etichette e selezione")
     * @test
     */
    public function it_correctly_resets_the_label_selection_environment()
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                     S ::= Cifra | S.nz [^S.] ;
                     Cifra ::= z: 0 | nz: >(1| 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9) ;
GRAMMAR
            ),
            Document::START
        );

        // This seed caused an invalid generation at the time of writing
        $generated = $polygen->generate($document, $context = Context::get(Document::START));

        $this->assertMatchesRegularExpression('{(0|[1-9][0-9]*)}', $generated, $context->getSeed());
    }

    /**
     * @test
     * @dataProvider provider_no_label_selection
     */
    public function it_can_select_any_label_if_no_selection_is_specified($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                     S ::= A.a | A;
                     A ::= a: a | b: b | c;
GRAMMAR
            ),
            Document::START
        );

        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));

        $acceptable = [
            'a',
            'b',
            'c'
        ];

        $this->assertContains($generated, $acceptable, $context->getSeed());
    }

    public function provider_no_label_selection()
    {
        // These three values covered all the possible outputs at the time of writing.
        return [
            ['1'],
            ['3'],
            ['11'],
        ];
    }

    /**
     * @test
     * @param string $seed
     * @dataProvider provider_multiple_label_selection
     */
    public function it_supports_multiple_label_selection($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                <<<GRAMMAR
                     S ::= (sei (M: un | F: una) (M: bel | F: bella) ragazz ^ (M: o | F: a)).(M|F) ;
GRAMMAR
            ),
            Document::START
        );

        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));

        $acceptable = [
          'sei una bella ragazza',
          'sei un bel ragazzo',
        ];

        $this->assertContains($generated, $acceptable, $context->getSeed());
    }

    static function provider_multiple_label_selection()
    {
        return [
            ['1'],
            ['2'],
            ['3'],
            ['4'],
            ['5'],
            ['6'],
            ['7'],
            ['8'],
            ['9'],
            ['0'],
        ];
    }

    /**
     * This example is taken directly from the Polygen documentation, section 2.0.12 "Generazione posizionale".
     * @test
     * @param string $seed
     * @dataProvider provider_positional_selection
     */
    public function it_supports_positional_selection($seed)
    {
        $polygen = new Polygen();
        $document = $polygen->getDocument(
            $this->given_a_source_stream(
                'S ::= sei un,una bel,bella ragazz ^ o,a ;'
            ),
            Document::START
        );

        $generated = $polygen->generate($document, $context = Context::get(Document::START, $seed));

        $acceptable = [
            'sei un bel ragazzo',
            'sei una bella ragazza',
        ];

        $this->assertContains($generated, $acceptable, "Positional selection failed for seed {$context->getSeed()}");
    }

    static function provider_positional_selection()
    {
        return [
            ['1'],
            ['2'],
            ['3'],
            ['4'],
            ['5'],
            ['6'],
            ['7'],
            ['8'],
            ['9'],
            ['0'],
        ];
    }
}
