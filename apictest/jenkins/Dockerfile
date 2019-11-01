FROM portal-site-template

COPY create_test_site.sh /tmp/
COPY run_tests.sh /tmp/
COPY module_name.txt /tmp/
COPY ibm_apim_devportal-8.x-*.tgz /opt/ibm/upgrade/
COPY modules/ /tmp/modules/

COPY node-v*-linux-x64.tar.gz /tmp/data/

RUN bash /tmp/create_test_site.sh FALSE

RUN yum install --tolerant --skip-broken bison byacc cscope ctags diffstat doxygen elfutils flex gcc gcc-c++ gcc-gfortran gettext indent intltool libtool patchutils rcs rpm-build rpm-sign subversion swig systemtap

RUN mkdir /tmp/node \
	&& cd /tmp/node \
	&& tar xfz /tmp/data/node-v*-linux-x64.tar.gz --strip 1 \
	&& cp -r lib/* /usr/local/lib \
	&& cp -P bin/* /usr/local/bin \
	&& rm -rf /tmp/node /tmp/data/node-*

RUN chmod +x /tmp/run_tests.sh
