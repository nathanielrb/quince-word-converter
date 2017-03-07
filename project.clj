(defproject w2m "0.1.0-SNAPSHOT"
  :description "FIXME: write description"
  :url "http://example.com/FIXME"
  :min-lein-version "2.0.0"
  :dependencies [[org.clojure/clojure "1.8.0"]
                 [org.clojure/data.codec "0.1.0"]
                 [compojure "1.5.1"]
                 [ring/ring-defaults "0.2.3"]
                 [ring "1.5.1"]
                 [cheshire "5.7.0"]
                 [clojure-saxon "0.9.4"]
                 [ring/ring-json "0.4.0"]
                 [ring-cors "0.1.9"]]
  :plugins [[lein-ring "0.9.7"]]
  :ring {:handler w2m.handler/app}
  :profiles
  {:dev {:dependencies [[javax.servlet/servlet-api "2.5"]
                        [ring/ring-mock "0.3.0"]]}})
