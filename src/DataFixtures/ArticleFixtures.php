<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use Doctrine\Migrations\Version\Factory;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = \Faker\Factory::create('fr_FR');

        /* créer 3 catégories fakées */
        for ($i = 1; $i <= 3; $i++) {
            $category = new Category();
            $category->setTitle($faker->sentence())
                ->setDescription($faker->paragraph());

            $manager->persist($category);


            /* créer entre 4 à 6 articles */
            for ($j = 1; $j <= mt_rand(4, 6); $j++) {
                $article = new Article();

                /* $faker->paragraphs() renvoit un tableau, et setContent attend une
            chaîne de caractères, donc : */
                // $content = '<p>';
                // $content .= join($faker->paragraphs(5), '<p><p>'); // .= : ajouter
                // $content .= '<p>';
                // ou en une ligne : 
                $content = '<p>' . join($faker->paragraphs(5), '<p><p>') . '<p>';

                $article->setTitle($faker->sentence())
                    ->setContent($content)
                    ->setImage($faker->imageUrl())
                    ->setCreatedAt($faker->dateTimeBetween('- 6 months'))
                    ->setCategory($category);

                $manager->persist($article);


                /* créer entre 4 et 10 commentaires */
                for ($k = 1; $k <= mt_rand(4, 10); $k++) {

                    $comment = new Comment();

                    /* idem que pour les articles */
                    $content = '<p>' . join($faker->paragraphs(2), '<p><p>') . '<p>';

                    /* pour date cohérente avec la date de l'article */
                    $now = new \DateTime();
                    $interval = $now->diff($article->getCreatedAt());
                    $days = $interval->days;
                    $minimum = '-' . $days . ' days ';

                    $comment->setAuthor($faker->name())
                        ->setContent($content)
                        ->setCreatedAt($faker->dateTimeBetween($minimum))
                        ->setArticle($article);

                    $manager->persist($comment);
                }
            }
        }

        $manager->flush();
    }
}
