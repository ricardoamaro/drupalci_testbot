require 'spec_helper'

describe file('/usr/local/bin/composer') do
  it { should be_file }
  it { should be_mode 775 }
end
