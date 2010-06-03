#!/usr/bin/ruby

require 'rubygems'
require 'benchmark'
require 'digest/md5'
require 'cassandra'
include SimpleUUID

twitter = Cassandra.new('Twitter', ['192.168.0.103:9160', '192.168.0.109:9160'])
# twitter = Cassandra.new('Twitter', ['192.168.0.103:9160'])

rows = ARGV[0]

if rows.nil?
  puts "usage: cass_build_tables.rb rows"
  exit
end


# puts twitter.get(:Users, '8').inspect
i = 0
rows.to_i.times do
  twitter.remove(:Users, "#{i}")
  i = i + 1
end

puts Time.now
Benchmark.bm(7) do |x|
x.report("inserts:") {
	rows.to_i.times do 
	  twitter.insert(:Users, "#{i}", {'login' => "buttonscat_#{i}", 
	  # 'email' => "buttonscat_#{i}@cyphgen.com",
	  'pass' => Digest::MD5.hexdigest("buttonscat_#{i}@cyphgen.com")
	  })
	
	  i = i + 1
	end 
}
x.report("finds:") {
  rows.to_i.times do
    twitter.get(:Users, "#{rand(rows)}")
  end
}


end
# end bmark

