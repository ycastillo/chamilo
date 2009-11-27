//
//  Copyright (C) 1999 AT&T Laboratories Cambridge.  All Rights Reserved.
//
//  This is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This software is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this software; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,
//  USA.
//

import java.awt.Button;
import java.awt.Color;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.Label;
import java.awt.Panel;
import java.awt.TextField;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

//
// The panel which implements the user authentication scheme
//

class AuthPanel extends Panel implements ActionListener {

  Label title, retry, prompt;
  TextField password;
  Button ok;
  
  // mslgon support
  Label promptuser; 
  TextField username;
  boolean mslogon = false;
  // mslogon support end
  
  //
  // Constructor.
  //

    // mslgon support 2
  public AuthPanel(boolean logon) {

    mslogon = logon;
    // mslgon support 2 end
	  
    title = new Label("VNC Authentication",Label.CENTER);
    title.setFont(new Font("Helvetica", Font.BOLD, 18));

    prompt = new Label("Password:",Label.CENTER);

    password = new TextField(10);
    password.setForeground(Color.black);
    password.setBackground(Color.white);
    password.setEchoChar('*');

    // mslogon support 3

    if (mslogon) {
    promptuser = new Label("Username:",Label.CENTER);
    username = new TextField(10);
    username.setForeground(Color.black);
    username.setBackground(Color.white);
    }
    // mslogon support 3 end
   
    ok = new Button("OK");

    retry = new Label("",Label.CENTER);
    retry.setFont(new Font("Courier", Font.BOLD, 16));


    GridBagLayout gridbag = new GridBagLayout();
    GridBagConstraints gbc = new GridBagConstraints();

    setLayout(gridbag);

    gbc.gridwidth = GridBagConstraints.REMAINDER;
    gridbag.setConstraints(title,gbc);
    add(title);

    gbc.fill = GridBagConstraints.HORIZONTAL;
    gridbag.setConstraints(retry,gbc);
    add(retry);

    gbc.fill = GridBagConstraints.NONE;
    gbc.gridwidth = 1;
  
    //mslogon support 4
   
    if (mslogon) { 
    gridbag.setConstraints(promptuser,gbc);
    add(promptuser);
    gridbag.setConstraints(username,gbc);
    add(username);
    username.addActionListener(this);
    }
    //mslogon support 4 end

    gridbag.setConstraints(prompt,gbc);
    add(prompt);

    gridbag.setConstraints(password,gbc);
    add(password);
    password.addActionListener(this);

    gbc.ipady = 10;
    gbc.gridwidth = GridBagConstraints.REMAINDER;
    gbc.fill = GridBagConstraints.BOTH;
    gbc.insets = new Insets(0,20,0,0);
    gbc.ipadx = 40;
    gridbag.setConstraints(ok,gbc);
    add(ok);
    ok.addActionListener(this);
  }

  // mslogon support 5
  public void setmslogon(boolean InfoMsLogon) {
    mslogon = InfoMsLogon;	 
  }

  public void moveFocusToUsernameField() {
    if (mslogon) { username.requestFocus(); }
    else { moveFocusToPasswordField();}
  }
  
  // mslogon support 5 end	  
	  
  //
  // Move keyboard focus to the password text field object.
  //

  public void moveFocusToPasswordField() {
    password.requestFocus();
  }

  //
  // This method is called when a button is pressed or return is
  // pressed in the password text field.
  //

  public synchronized void actionPerformed(ActionEvent evt) {
    if (evt.getSource() == password || evt.getSource() == ok) {
      password.setEnabled(false);
      notify();
    }
  }

  //
  // retry().
  //

  public void retry() {
    retry.setText("Sorry. Try again.");
    password.setEnabled(true);
    password.setText("");
    moveFocusToPasswordField();
  }

}
