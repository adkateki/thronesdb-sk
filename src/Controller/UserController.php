<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\CardInterface;
use App\Entity\Decklist;
use App\Entity\DecklistInterface;
use App\Entity\Faction;
use App\Entity\ReviewInterface;
use App\Entity\User;
use App\Entity\UserInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @package App\Controller
 */
class UserController extends Controller
{
    /**
     * @Route(
     *     "/user/profile/{user_id}/{user_name}/{page}",
     *     name="user_profile_public",
     *     methods={"GET"},
     *     defaults={"page"=1},
     *     requirements={"user_id"="\d+"}
     * )
     * @param int $user_id
     * @param string $user_name
     * @param int $page
     * @param Request $request
     * @return Response
     */
    public function publicProfileAction($user_id, $user_name, $page, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var UserInterface $user */
        $user = $em->getRepository(User::class)->find($user_id);
        if (! $user) {
            throw new NotFoundHttpException("No such user.");
        }

        return $this->render('User/profile_public.html.twig', array(
                'user'=> $user
        ));
    }

    /**
     * @Route("/user/profile_edit", name="user_profile_edit", methods={"GET"})
     * @return Response
     */
    public function editProfileAction()
    {
        $user = $this->getUser();

        $factions = $this->getDoctrine()->getRepository(Faction::class)->findAll();

        return $this->render('User/profile_edit.html.twig', array(
                'user' => $user,
                'factions' => $factions
        ));
    }

    /**
     * @Route("/user/profile_save", name="user_profile_save", methods={"POST"})
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveProfileAction(Request $request)
    {
        /* @var UserInterface $user */
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $username = filter_var($request->get('username'), FILTER_SANITIZE_STRING);
        if ($username !== $user->getUsername()) {
            $user_existing = $em->getRepository(User::class)->findOneBy(array('username' => $username));

            if ($user_existing) {
                $this->get('session')
                ->getFlashBag()
                ->set('error', "Username $username is already taken.");

                return $this->redirect($this->generateUrl('user_profile_edit'));
            }

            $user->setUsername($username);
        }

        $email = filter_var($request->get('email'), FILTER_SANITIZE_STRING);
        if ($email !== $user->getEmail()) {
            $user->setEmail($email);
        }

        $resume = filter_var($request->get('resume'), FILTER_SANITIZE_STRING);
        $faction_code = filter_var($request->get('user_faction_code'), FILTER_SANITIZE_STRING);
        $notifAuthor = $request->get('notif_author') ? true : false;
        $notifCommenter = $request->get('notif_commenter') ? true : false;
        $notifMention = $request->get('notif_mention') ? true : false;
        $shareDecks = $request->get('share_decks') ? true : false;

        $user->setColor($faction_code);
        $user->setResume($resume);
        $user->setIsNotifAuthor($notifAuthor);
        $user->setIsNotifCommenter($notifCommenter);
        $user->setIsNotifMention($notifMention);
        $user->setIsShareDecks($shareDecks);

        $this->getDoctrine()->getManager()->flush();

        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Successfully saved your profile.");

        return $this->redirect($this->generateUrl('user_profile_edit'));
    }

    /**
     * @Route("/api/public/user/info", name="user_info", methods={"GET"}, options={"i18n" = false})
     * @param Request $request
     * @return Response
     */
    public function infoAction(Request $request)
    {
        $jsonp = $request->query->get('jsonp');

        $decklist_id = $request->query->get('decklist_id');
        $card_id = $request->query->get('card_id');

        $content = null;

        $authorizationChecker = $this->container->get('security.authorization_checker');
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            /** @var UserInterface $user */
            $user = $this->getUser();
            $user_id = $user->getId();

            $public_profile_url = $this->get('router')->generate('user_profile_public', array(
                    'user_id' => $user_id,
                    'user_name' => urlencode($user->getUsername())
            ));
            $content = array(
                    'public_profile_url' => $public_profile_url,
                    'id' => $user_id,
                    'name' => $user->getUsername(),
                    'faction' => $user->getColor(),
                    'donation' => $user->getDonation(),
            );

            if (isset($decklist_id)) {
                /* @var EntityManager $em */
                $em = $this->getDoctrine()->getManager();
                /* @var DecklistInterface $decklist */
                $decklist = $em->getRepository(Decklist::class)->find($decklist_id);

                if ($decklist) {
                    $decklist_id = $decklist->getId();

                    $dbh = $this->getDoctrine()->getConnection();

                    $content['is_liked'] = (boolean) $dbh->executeQuery("SELECT
                        count(*)
                        from decklist d
                        join vote v on v.decklist_id=d.id
                        where v.user_id=?
                        and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];

                    $content['is_favorite'] = (boolean) $dbh->executeQuery("SELECT
                        count(*)
                        from decklist d
                        join favorite f on f.decklist_id=d.id
                        where f.user_id=?
                        and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];

                    $content['is_author'] = ($user_id == $decklist->getUser()->getId());

                    $content['can_delete'] = ($decklist->getNbcomments() == 0)
                        && ($decklist->getNbfavorites() == 0)
                        && ($decklist->getnbVotes() == 0);
                }
            }

            if (isset($card_id)) {
                /* @var $em EntityManager */
                $em = $this->getDoctrine()->getManager();
                /* @var CardInterface $card */
                $card = $em->getRepository(Card::class)->find($card_id);

                if ($card) {
                    $reviews = $card->getReviews();
                    /* @var ReviewInterface $review */
                    foreach ($reviews as $review) {
                        if ($review->getUser()->getId() === $user->getId()) {
                            $content['review_id'] = $review->getId();
                            $content['review_text'] = $review->getTextMd();
                        }
                    }
                }
            }
        }
        $content = json_encode($content);

        $response = new Response();
        $response->setPrivate();
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/user/remind/{username}", name="remind_email", methods={"GET"})
     *
     * @param $username
     * @return RedirectResponse|Response
     */
    public function remindAction($username)
    {
        $user = $this->get('fos_user.user_manager')->findUserByUsername($username);
        if (!$user) {
            throw new NotFoundHttpException("Cannot find user from username [$username]");
        }
        if (!$user->getConfirmationToken()) {
            return $this->render('User/remind-no-token.html.twig');
        }

        $this->get('fos_user.mailer')->sendConfirmationEmailMessage($user);

        $this->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

        $url = $this->get('router')->generate('fos_user_registration_check_email');
        return $this->redirect($url);
    }
}