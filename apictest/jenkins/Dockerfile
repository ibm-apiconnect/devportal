FROM portal-site-template

COPY create_test_site.sh /tmp/
COPY run_tests.sh /tmp/
COPY module_name.txt /tmp/
COPY ibm_apim_devportal-8.x-*.tgz /opt/ibm/upgrade/
COPY modules/ /tmp/modules/

RUN bash /tmp/create_test_site.sh FALSE

RUN apt-get update && \
	apt-get install -y python build-essential git

RUN mkdir /tmp/nvm \
        && curl -o- https://codeload.github.com/creationix/nvm/tar.gz/v0.33.8 | tar xfz - --strip-components=1 -C /tmp/nvm \
        && cd /tmp/nvm \
        && METHOD=git ./install.sh \
        && cd - \
        && export NVM_DIR="$HOME/.nvm" \
        && . "$NVM_DIR/nvm.sh" \
        && nvm install 6.3.0

RUN chmod +x /tmp/run_tests.sh