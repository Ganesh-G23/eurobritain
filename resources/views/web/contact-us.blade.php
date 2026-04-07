@extends('web.layouts.app')

@section('title', 'Welcome to EliteGrade')

@section('css')
    <style>
        header.float-start.w-100 {
            position: absolute;
            z-index: 10;
        }

        .contact-inner-banner img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
        }

        .contact-inner-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.25);
        }

        .contact-inner-banner .inner_banner_caption {
            z-index: 1;
        }

        @media (max-width: 768px) {
            .contact-inner-banner img {
                height: 180px;
            }
        }
    </style>
@endsection

@section('content')

    <div class="inner_banner_section contact-inner-banner position-relative">
        <img src="{{ url('public/web_theme/assets/images/inner-banner.jpg') }}" alt="" class="img-fluid">
        <div class="inner_banner_caption">Contact Us</div>
    </div>

    <div class="about_us_section float-start w-100" style="background-color: #FFF;">
        <div class="container">
            <div class="contact-us-section">

                <div class="row">
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12" style="float:none; margin:0 auto;">
                        <div class="enquay-form-section"
                            style="align-items: flex-start; border: 1px solid #c1c1c1; border-radius: 10px;">
                            <h3 style="color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"
                                class="mb-3">Get in Touch </h3>
                            <form class="form-horizontal" action="">
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" id="name" placeholder="Name"
                                        name="name">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" id="email" placeholder="Email"
                                        name="Email">
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control" id="contact-no" placeholder="Phone No."
                                        name="Contact-no">
                                </div>
                                <div class="form-group mb-3">
                                    <textarea class="form-control" id="cmessage" name="message" rows="7" placeholder="Message" required=""
                                        data-validation-required-message="Please enter your message."></textarea>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn-main">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12" style="display:none;">
                        <div class="contact-us-right-section margin_extra">
                            <h3 style="color: #000; font-weight: 700; letter-spacing: 1px;" class="mb-3">CONTACTS </h3>
                            <address><span><strong>Address</strong></span><br>
                                Lorem Ipsum is simply dummy text of <br>the printing and typesetting industry simply.
                                <br>Lorem Ipsum is simply dummy
                            </address>
                            <p><span><strong>Email:-</strong></span><br> <a href="mailto:swarnshanti2016@gmail.com"
                                    class="text-black text-decoration-none">elitegradegmail.com</a></p>
                            <p><span><strong>Mobile No:-</strong></span><br>91 12345-67899</p>
                            <div class="social-media">
                                <span><strong>Social Media</strong></span>
                                <ul>
                                    <li><a href="#"><img width="40" height="40"
                                                src="https://img.icons8.com/ios-filled/100/facebook--v1.png"
                                                alt="facebook--v1" /></a></li>
                                    <li><a href="#"><img width="40" height="40"
                                                src="https://img.icons8.com/ios-filled/100/instagram-new--v1.png"
                                                alt="instagram-new--v1" /></a></li>
                                    <li><a href="#"><img width="40" height="40"
                                                src="https://img.icons8.com/ios-filled/100/linkedin.png"
                                                alt="linkedin" /></a></li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="about_us_section call-to-action float-start w-100">
        <div class="container">
            <h3 class="main-title text-black text-center mb-2">Ready to Transform Your Education?</h3>
            <p class="text-black mb-0 text-center">Join EliteGrade today and experience the future of educational
                management.</p>
            <a href="{{ url('/contact') }}" class="btn-main header-btn mt-0"
                style="background-color: #FFF; color: #d8a850; width: 180px;">Get in Touch</a>
        </div>
    </div>
@endsection
