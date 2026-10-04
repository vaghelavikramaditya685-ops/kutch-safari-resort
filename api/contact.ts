// Vercel function for a contact form. Nothing stores enquiries on Vercel yet, so it
// must not answer "success": an enquiry would be lost while the guest is told it
// arrived (it used to log the whole enquiry — name, email, phone — to the Vercel
// logs and reply "Enquiry processed successfully", even to an empty post). It checks
// the fields and says plainly where to send the enquiry instead. To keep enquiries,
// post them to the booking engine's api/enquiry.php, which checks and stores them.
export default function handler(req: any, res: any) {
  if (req.method !== 'POST') {
    res.setHeader('Allow', 'POST');
    res.status(405).json({ success: false, message: 'POST required.' });
    return;
  }
  const b = req.body && typeof req.body === 'object' ? req.body : {};
  const name = typeof b.name === 'string' ? b.name.trim() : '';
  const contact = [b.phone, b.email].some((v: unknown) => typeof v === 'string' && v.trim() !== '');
  const message = typeof b.message === 'string' ? b.message.trim() : '';
  if (!name || !contact || !message) {
    res.status(400).json({ success: false, message: 'Please give your name, a phone number or email address, and a message.' });
    return;
  }
  console.log('Contact form: an enquiry arrived but is not stored here (no personal details logged).');
  res.status(503).json({
    success: false,
    message: 'We could not receive your message online just now. Please call or WhatsApp us on +91 99252 38599.',
  });
}
