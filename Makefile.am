
AM_CFLAGS = -I$(top_srcdir)/include
AM_CXXFLAGS = $(WARN_CXXFLAGS) -I$(top_srcdir)/include
# AM_CXXFLAGS = $(BOOST_CPPFLAGS) -I$(top_srcdir)/include
# AM_LDFLAGS = $(BOOST_LDFLAGS)

bin_PROGRAMS = atlas
atlas_SOURCES = src/main.cpp

atlas_CFLAGS = $(AM_CFLAGS) $(CFLAGS_PCH)
atlas_CXXFLAGS = $(AM_CXXFLAGS) $(CXXFLAGS_PCH)
atlas_LDADD = $(LDADD) # $(AM_BOOST_LIBS)

