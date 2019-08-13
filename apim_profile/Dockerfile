FROM portal-site-template

COPY site-template_setup.sh /tmp/
COPY build/ibm_apim_devportal-8.x-*.tgz /opt/ibm/upgrade/
RUN scl enable rh-php72 -- bash /tmp/site-template_setup.sh
