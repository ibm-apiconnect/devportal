FROM portal-site-template-with-site

RUN apt-get update && \
	apt-get install -y python build-essential git

RUN mkdir /tmp/nvm \
        && curl -o- https://codeload.github.com/creationix/nvm/tar.gz/v0.33.8 | tar xfz - --strip-components=1 -C /tmp/nvm \
        && cd /tmp/nvm \
        && METHOD=git ./install.sh \
        && cd - \
        && export NVM_DIR="$HOME/.nvm" \
        && . "$NVM_DIR/nvm.sh" \
        && nvm install --lts

COPY site-template_test.sh /tmp/

RUN chmod +x /tmp/site-template_test.sh
