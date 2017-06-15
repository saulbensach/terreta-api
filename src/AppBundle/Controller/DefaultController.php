<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Member;
use AppBundle\Entity\Payment;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ));
    }
    /**
     * @Route("/getMembers", name="get_members")
     */
    public function getMembersAction()
    {
      $data = array();
      $membersRepository = $this->getDoctrine()->getRepository('AppBundle:Member');
      $paymentRepository = $this->getDoctrine()->getRepository('AppBundle:Payment');
      $members = $membersRepository->findAll();
      foreach ($members as $m) {
        $payments = $paymentRepository->findBy(array('member' => $m->getId()));
        array_push($data, array("member" => $m, "payments" => $payments));
      }
      return new Response($this->encrypt_decrypt('encrypt',json_encode($data)));
    }

    /**
     * @Route("/addMember", name="add_member")
     */
    public function addMemberAction(Request $request)
    {
      $jsonString = $this->encrypt_decrypt('decrypt', $request->request->get('data'));
      $data = json_decode($jsonString, true);
      $member = new Member();
      $member->setNif     ($data["nif"]);
      $member->setName    ($data["name"]);
      $member->setSurname ($data["surname"]);
      $member->setEmail   ($data["email"]);
      $member->setPhone   ($data["phone"]);

      $em = $this->getDoctrine()->getManager();
      $em->persist($member);
      $em->flush();

      //TODO añadir encriptación
      return new Response("Miembro añadido con id: ".$member->getId());
    }

    /**
     * @Route("/addPayment", name="add_payment")
     */
    public function addPaymentAction(Request $request)
    {
      $jsonString = $this->encrypt_decrypt('decrypt', $request->request->get('data'));
      $data = json_decode($jsonString, true);

      $repository = $this->getDoctrine()->getRepository('AppBundle:Member');
      $member = $repository->findOneById($data["member"]);

      $payment = new Payment();
      $payment->setMember($member);
      $payment->setPrice($data["price"]);
      $payment->setDate($data["date"]);

      $em = $this->getDoctrine()->getManager();
      $em->persist($payment);
      $em->flush();
    
      return new Response("Pago añadido con id: ".$payment->getId());
    }

    /**
     * @Route("/test", name="test")
     */
    public function testAction(Request $request)
    {
        $tocyp = $request->request->get('data');
        $secret = $this->encrypt_decrypt('encrypt', $tocyp);
        return new Response($secret);
    }

    public function encrypt_decrypt($action, $string)
    {
      $output = false;
      $key = "57238004e784498bbc2f8bf984565090";
      if($action == 'encrypt'){
        $output = $this->encrypt($string, $key);
      }else if($action == 'decrypt'){
        $output = $this->decrypt($string, $key);
      }

      return $output;
    }

    public function encrypt($input, $key)
    {
  		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
  		$input = $this->pkcs5_pad($input, $size);
  		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
  		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
  		mcrypt_generic_init($td, $key, $iv);
  		$data = mcrypt_generic($td, $input);
  		mcrypt_generic_deinit($td);
  		mcrypt_module_close($td);
  		$data = base64_encode($data);
  		return $data;
	  }
    private function pkcs5_pad ($text, $blocksize)
    {
    	$pad = $blocksize - (strlen($text) % $blocksize);
    	return $text . str_repeat(chr($pad), $pad);
    }

    public function decrypt($sStr, $sKey)
    {
      $decrypted= mcrypt_decrypt(
  		MCRYPT_RIJNDAEL_128,
  		$sKey,
  		base64_decode($sStr),
    	MCRYPT_MODE_ECB);
      $dec_s = strlen($decrypted);
      $padding = ord($decrypted[$dec_s-1]);
      $decrypted = substr($decrypted, 0, -$padding);
  	  return $decrypted;
    }
}
