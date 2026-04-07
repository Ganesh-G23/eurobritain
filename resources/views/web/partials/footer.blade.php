<!--footer-->
@php $contact = contactus(); @endphp
<style>
  #newsletterMsg {
    display: block;
    margin-top: 5px;
    font-size: 14px;
  }
</style>
<section class="newslettre__section">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-6 col-md-10 col-sm-11 m-auto">
        <div class="newslettre">
          <div class="newslettre__info ">
            <h3 class="newslettre__title">Don't Miss Out on the Latest Updates.</h3>
            <p class="newslettre__desc"> Subscribe to Our Newsletter Today! </p>
          </div>

          <form id="newsletterForm" class="newslettre__form">
            @csrf

            <input type="email" name="email" id="newsletterEmail"
              class="newslettre__form-input form-control"
              placeholder="Your email address">
            <button class="newslettre__form-submit" type="submit">Subscribe</button>
            <small id="newsletterMsg" class="form-text"></small>

          </form>
          <ul class="list-inline social-media social-media--layout-three">
            <li class="social-media__item"> <a href="{{$contact->facebook}}" class="social-media__link"><i
                  class="bi bi-facebook"></i>Facebook</a> </li>
            <li class="social-media__item"> <a href="{{$contact->instagram}}" class="social-media__link"><i
                  class="bi bi-instagram"></i>Instagram</a> </li>
            <li class="social-media__item"> <a href="{{$contact->company_url}}" class="social-media__link"><i
                  class="bi bi-twitter-x"></i>Twitter</a> </li>
            <li class="social-media__item"> <a href="{{$contact->youtube_url}}" class="social-media__link"><i
                  class="bi bi-youtube"></i>Youtube</a> </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>
<footer class="footer">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-12">
        <div class="footer__copyright">
          <p class="footer__copyright-text">© Copyright {{ date('Y') }} <a href="{{ route('web.home') }}" class="footer__copyright-link">elitegrade</a>, All rights reserved. </p>
        </div>
        <div class="btn-back-top"> <a href="#" class="btn-back-top__link"> <i class="bi bi-arrow-up btn-back-top__icon"></i> </a> </div>
      </div>
    </div>
  </div>
</footer>
<script src="{{ url('public/web_theme/assets/js/jquery.min.js') }}"></script>
<script>
  $('#newsletterForm').submit(function(e) {
    e.preventDefault();

    $('#newsletterMsg').html('').removeClass('text-danger text-success');

    $.ajax({
      url: "{{ route('newsletter.save') }}",
      type: "POST",
      data: $(this).serialize(),
      success: function(response) {
        if (response.status === 1) {
          $('#newsletterMsg')
            .addClass('text-success')
            .html(response.msg);

          $('#newsletterForm')[0].reset();
        } else {
          $('#newsletterMsg')
            .addClass('text-danger')
            .html(response.errors.email[0]);
        }
      },
      error: function(xhr) {
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;
          $('#newsletterMsg')
            .addClass('text-danger')
            .html(errors.email[0]);
        }
      }
    });
  });
</script>